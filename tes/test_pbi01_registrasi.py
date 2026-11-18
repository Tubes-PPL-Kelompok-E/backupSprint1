"""PBI-01 Registrasi akun (Pendonor, Petugas RS, Admin PMI dengan kode rahasia).

Rute POST /register dibatasi 5 kali per menit per IP. Tabel di bawah hanya memuat 5 TC yang
sampai ke server (TC-01..05); sisanya ditolak browser. Tunggu 60 detik sebelum menjalankan ulang.
"""

import os
import re
import uuid
from datetime import date, timedelta
from urllib.parse import urlparse

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

BASE_URL = os.getenv("BASE_URL", "http://127.0.0.1:8000")
TIMEOUT = 10

# Kotak pesan pada layout aplikasi (flash sukses = hijau, kesalahan = merah)
KOTAK_ERROR = (By.CSS_SELECTOR, "div.bg-red-50.border-red-200")
KOTAK_SUKSES = (By.CSS_SELECTOR, "div.bg-green-50.border-green-200")

# Akun uji dari database/seeders/UserSeeder.php
AKUN = {
    "admin_pmi": "admin.pmi@bloodconnect.id",
    "petugas_rs": "petugas.rs@bloodconnect.id",
    "pendonor": "pendonor@bloodconnect.id",   # terhubung ke "Pendonor Contoh" (DemoSeeder)
}
PASSWORD_BENAR = "password123"


@pytest.fixture
def driver():
    options = webdriver.ChromeOptions()
    if os.getenv("HEADLESS") == "1":
        options.add_argument("--headless=new")
    d = webdriver.Chrome(options=options)
    d.maximize_window()
    yield d
    d.quit()


def buka(driver, url):
    driver.get(BASE_URL + url)


def path_sekarang(driver):
    return urlparse(driver.current_url).path


def uniq(awal=""):
    return f"{awal}{uuid.uuid4().hex[:8]}"


def hari(selisih=0):
    """Tanggal ISO hari ini ditambah selisih hari."""
    return (date.today() + timedelta(days=selisih)).isoformat()


def klik(driver, by, selector):
    el = WebDriverWait(driver, TIMEOUT).until(EC.presence_of_element_located((by, selector)))
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
    WebDriverWait(driver, TIMEOUT).until(EC.element_to_be_clickable((by, selector)))
    el.click()


def isi(driver, name, nilai):
    """Isi field; nilai kosong ('' atau None) berarti field dikosongkan."""
    el = WebDriverWait(driver, TIMEOUT).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, f"[name='{name}']")))
    el.clear()
    if nilai is not None and str(nilai) != "":
        el.send_keys(str(nilai))


def isi_js(driver, name, nilai):
    """Untuk input date/time agar tidak bergantung pada locale browser."""
    el = driver.find_element(By.CSS_SELECTOR, f"[name='{name}']")
    driver.execute_script(
        "arguments[0].value = arguments[1];"
        "arguments[0].dispatchEvent(new Event('input', {bubbles:true}));"
        "arguments[0].dispatchEvent(new Event('change', {bubbles:true}));", el, str(nilai))


def pilih(driver, name, value):
    Select(driver.find_element(By.CSS_SELECTOR, f"select[name='{name}']")).select_by_value(value)


def radio(driver, name, value):
    """Radio golongan/rhesus/hasil uji disembunyikan (sr-only), jadi diklik lewat JS."""
    driver.execute_script(
        "document.querySelector(\"input[name='%s'][value='%s']\").click();" % (name, value))


def setuju_dialog(driver):
    WebDriverWait(driver, TIMEOUT).until(EC.alert_is_present()).accept()


def login(driver, role):
    """Masuk lewat halaman login dengan akun seeder sesuai peran."""
    buka(driver, "/login")
    klik(driver, By.CSS_SELECTOR, f"button[data-role='{role}']")
    isi(driver, "email", AKUN[role])
    isi(driver, "password", PASSWORD_BENAR)
    klik(driver, By.CSS_SELECTOR, "form button:not([type='button'])")
    WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != "/login")


def cek_hasil(driver, expected_type, expected_value):
    """Pemeriksa hasil bersama, tiap jenis hasil sama seperti pada test_login."""
    if expected_type == "url":
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains(expected_value))
        assert path_sekarang(driver) == expected_value

    elif expected_type == "keluar_form":
        # Berpindah dari halaman form = server menerima data
        WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != expected_value)

    elif expected_type == "error":
        pesan = WebDriverWait(driver, TIMEOUT).until(
            EC.visibility_of_element_located(KOTAK_ERROR)
        ).text
        assert expected_value in pesan

    elif expected_type == "sukses":
        pesan = WebDriverWait(driver, TIMEOUT).until(
            EC.visibility_of_element_located(KOTAK_SUKSES)
        ).text
        assert expected_value in pesan

    elif expected_type == "html5_required":
        field = driver.find_element(By.CSS_SELECTOR, f"[name='{expected_value}']")
        assert driver.execute_script("return arguments[0].validity.valueMissing;", field)

    elif expected_type == "html5_invalid":
        # Batas min/max/type ditolak browser sehingga form tidak terkirim
        field = driver.find_element(By.CSS_SELECTOR, f"[name='{expected_value}']")
        assert not driver.execute_script("return arguments[0].validity.valid;", field)

    elif expected_type == "halaman_memuat":
        assert expected_value in driver.page_source

    elif expected_type == "403":
        assert "403" in driver.page_source or "Forbidden" in driver.page_source

    else:
        raise AssertionError(f"expected_type tidak dikenal: {expected_type}")


KODE_ADMIN = os.getenv("ADMIN_REGISTER_CODE", "PMI-ADMIN-2026")   # config/bloodconnect.php


def isi_registrasi(driver, role, nama, email, instansi, kode, sandi, konfirmasi):
    buka(driver, "/register")
    klik(driver, By.CSS_SELECTOR, f"button[data-role='{role}']")
    isi(driver, "name", nama)
    isi(driver, "email", f"{uniq('reg.')}@bloodconnect.id" if email == "AUTO" else email)
    isi(driver, "instansi", instansi)
    if role == "admin_pmi":
        isi(driver, "kode_admin", kode)
    isi(driver, "password", sandi)
    isi(driver, "password_confirmation", konfirmasi)
    klik(driver, By.CSS_SELECTOR, "form button:not([type='button'])")


@pytest.mark.parametrize(
    "role,nama,email,instansi,kode,sandi,konfirmasi,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: Pendonor dengan data benar
        ("pendonor", "Uji Registrasi", "AUTO", "Kampus", "", "rahasia123", "rahasia123", "url", "/pendonor/dashboard"),
        # TC-02  EP kelas valid: Admin PMI dengan kode registrasi benar
        ("admin_pmi", "Admin Uji", "AUTO", "PMI Uji", KODE_ADMIN, "rahasia123", "rahasia123", "url", "/admin/dashboard"),
        # TC-03  EP kelas invalid: kode registrasi Admin PMI salah
        ("admin_pmi", "Admin Uji", "AUTO", "PMI Uji", "KODE-SALAH", "rahasia123", "rahasia123", "error", "Kode registrasi Admin PMI salah."),
        # TC-04  EP kelas invalid: kata sandi tanpa angka (aturan: min 8, huruf + angka)
        ("pendonor", "Uji Registrasi", "AUTO", "Kampus", "", "hurufsaja", "hurufsaja", "error", ""),
        # TC-05  EP kelas invalid: email sudah terdaftar (akun seeder)
        ("pendonor", "Uji Registrasi", AKUN["admin_pmi"], "Kampus", "", "rahasia123", "rahasia123", "error", "Email sudah terdaftar. Silakan login."),
        # TC-06  EP kelas invalid: nama kosong (ditolak validasi HTML5)
        ("pendonor", "", "AUTO", "Kampus", "", "rahasia123", "rahasia123", "html5_required", "name"),
        # TC-07  EP kelas invalid: format email salah (ditolak validasi HTML5)
        ("pendonor", "Uji Registrasi", "bukan-email", "Kampus", "", "rahasia123", "rahasia123", "html5_invalid", "email"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06", "TC-07"],
)
def test_registrasi(driver, role, nama, email, instansi, kode, sandi, konfirmasi,
                    expected_type, expected_value):
    isi_registrasi(driver, role, nama, email, instansi, kode, sandi, konfirmasi)
    cek_hasil(driver, expected_type, expected_value)


def test_kolom_kode_admin_hanya_tampil_di_tab_admin(driver):
    # Kelas tampilan: kolom kode registrasi hanya muncul pada tab Admin PMI
    buka(driver, "/register")
    klik(driver, By.CSS_SELECTOR, "button[data-role='pendonor']")
    assert not driver.find_element(By.ID, "kodeWrap").is_displayed()
    klik(driver, By.CSS_SELECTOR, "button[data-role='admin_pmi']")
    assert driver.find_element(By.ID, "kodeWrap").is_displayed()
