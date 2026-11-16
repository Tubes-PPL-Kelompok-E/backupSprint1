import os
import uuid
from urllib.parse import urlparse

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

BASE_URL = os.getenv("BASE_URL", "http://127.0.0.1:8000")
URL = f"{BASE_URL}/login"
TIMEOUT = 10

# Kotak pesan kesalahan pada form login (flash sukses memakai bg-green-50)
KOTAK_ERROR = (By.CSS_SELECTOR, "form div.bg-red-50")

# Akun uji dari database/seeders/UserSeeder.php
ADMIN_PMI = "admin.pmi@bloodconnect.id"
PETUGAS_RS = "petugas.rs@bloodconnect.id"
PASSWORD_BENAR = "password123"


@pytest.fixture
def driver():
    options = webdriver.ChromeOptions()
    if os.getenv("HEADLESS") == "1":
        options.add_argument("--headless=new")
    d = webdriver.Chrome(options=options)
    d.maximize_window()
    d.get(URL)
    yield d
    d.quit()


def login(driver, role, email, password):
    """Pilih tab peran, isi email dan kata sandi, lalu klik tombol Masuk."""
    tab_role = WebDriverWait(driver, TIMEOUT).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, f"button[data-role='{role}']"))
    )
    tab_role.click()

    email_input = WebDriverWait(driver, TIMEOUT).until(
        EC.visibility_of_element_located((By.NAME, "email"))
    )
    email_input.clear()
    email_input.send_keys(email)

    password_input = driver.find_element(By.NAME, "password")
    password_input.clear()
    password_input.send_keys(password)

    # Tombol submit utama (bukan tombol tab peran yang bertipe button)
    driver.find_element(By.CSS_SELECTOR, "form button:not([type='button'])").click()


def path_sekarang(driver):
    return urlparse(driver.current_url).path


@pytest.mark.parametrize(
    "role,email,password,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: kredensial cocok dan peran sesuai
        ("admin_pmi", ADMIN_PMI, PASSWORD_BENAR, "url", "/admin/dashboard"),
        # TC-02  EP kelas invalid: kata sandi salah
        ("admin_pmi", ADMIN_PMI, "salah123", "error", "Email atau kata sandi salah."),
        # TC-03  EP kelas invalid: email kosong (ditolak validasi HTML5 di browser)
        ("admin_pmi", "", PASSWORD_BENAR, "html5_required", "email"),
        # TC-04  EP kelas invalid: kredensial benar tetapi tab peran tidak sesuai
        ("petugas_rs", ADMIN_PMI, PASSWORD_BENAR, "error",
         "Akun ini tidak terdaftar pada peran yang dipilih."),
        # TC-05  Kelas baru: akun terkunci setelah 5 percobaan gagal (rate limit 5x/60 dtk)
        ("admin_pmi", "AUTO", "salah123", "rate_limit", "Terlalu banyak percobaan login"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05"],
)
def test_login(driver, role, email, password, expected_type, expected_value):
    if expected_type == "url":
        login(driver, role, email, password)
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains(expected_value))
        assert path_sekarang(driver) == expected_value

    elif expected_type == "error":
        login(driver, role, email, password)
        pesan = WebDriverWait(driver, TIMEOUT).until(
            EC.visibility_of_element_located(KOTAK_ERROR)
        ).text
        assert expected_value in pesan
        assert path_sekarang(driver) == "/login"

    elif expected_type == "html5_required":
        login(driver, role, email, password)
        field = driver.find_element(By.NAME, expected_value)
        # valueMissing bersifat tegas dan tidak bergantung pada bahasa browser
        assert driver.execute_script("return arguments[0].validity.valueMissing;", field)
        # Form tidak terkirim: tetap di /login dan tidak ada pesan dari server
        assert path_sekarang(driver) == "/login"
        assert not driver.find_elements(*KOTAK_ERROR)

    elif expected_type == "rate_limit":
        # Email unik per eksekusi agar akun seeder tidak ikut terkunci
        # dan tes aman dijalankan ulang tanpa menunggu 60 detik.
        email_unik = f"uji.{uuid.uuid4().hex[:8]}@bloodconnect.id"

        kotak_lama = None
        for percobaan in range(1, 7):
            login(driver, role, email_unik, password)
            # Tunggu halaman lama diganti halaman hasil submit (bukan sleep),
            # supaya kotak error percobaan sebelumnya tidak terbaca ulang.
            if kotak_lama is not None:
                WebDriverWait(driver, TIMEOUT).until(EC.staleness_of(kotak_lama))
            kotak_lama = WebDriverWait(driver, TIMEOUT).until(
                EC.visibility_of_element_located(KOTAK_ERROR)
            )
            if percobaan <= 5:
                assert "Email atau kata sandi salah." in kotak_lama.text
            else:
                # Percobaan ke-6 harus diblokir
                assert expected_value in kotak_lama.text
                assert path_sekarang(driver) == "/login"