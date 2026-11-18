"""PBI-12 Kegiatan donor (dibuat Admin PMI) dan pendaftaran peserta oleh Pendonor."""

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


def isi_kegiatan(driver, judul, kuota, tanggal, mulai, selesai):
    buka(driver, "/admin/kegiatan-donor/buat")
    isi(driver, "judul", judul)
    isi_js(driver, "tanggal", hari(tanggal))
    isi_js(driver, "jam_mulai", mulai)
    isi_js(driver, "jam_selesai", selesai)
    isi(driver, "nama_tempat", "Balai Uji")
    isi(driver, "kuota", kuota)
    isi(driver, "alamat", "Jl. Uji Coba No. 2")
    klik(driver, By.XPATH, "//button[normalize-space()='Simpan Kegiatan']")


@pytest.mark.parametrize(
    "kuota,tanggal,mulai,selesai,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: kuota 10, tanggal +120 hari, 08:00-14:00
        (10, 120, "08:00", "14:00", "keluar_form", "/admin/kegiatan-donor/buat"),
        # TC-02  BVA kuota 0: di bawah batas bawah 1
        (0, 120, "08:00", "14:00", "html5_invalid", "kuota"),
        # TC-03  BVA kuota 1: batas bawah
        (1, 120, "08:00", "14:00", "keluar_form", "/admin/kegiatan-donor/buat"),
        # TC-04  BVA kuota 5000: batas atas
        (5000, 120, "08:00", "14:00", "keluar_form", "/admin/kegiatan-donor/buat"),
        # TC-05  BVA kuota 5001: di atas batas atas
        (5001, 120, "08:00", "14:00", "html5_invalid", "kuota"),
        # TC-06  EP kelas invalid: tanggal kegiatan kemarin
        (10, -1, "08:00", "14:00", "html5_invalid", "tanggal"),
        # TC-07  EP kelas invalid: jam selesai (08:00) sebelum jam mulai (12:00)
        (10, 120, "12:00", "08:00", "error", ""),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06", "TC-07"],
)
def test_buat_kegiatan(driver, kuota, tanggal, mulai, selesai, expected_type, expected_value):
    login(driver, "admin_pmi")
    isi_kegiatan(driver, "Donor " + uniq(), kuota, tanggal, mulai, selesai)
    cek_hasil(driver, expected_type, expected_value)


def test_pendonor_daftar_lalu_batal(driver):
    # Tanggal +120 hari agar lolos aturan jeda 60 hari dari donasi terakhir
    login(driver, "admin_pmi")
    judul = "Donor " + uniq()
    isi_kegiatan(driver, judul, 1, 120, "08:00", "14:00")
    cek_hasil(driver, "keluar_form", "/admin/kegiatan-donor/buat")

    driver.delete_all_cookies()
    login(driver, "pendonor")
    buka(driver, "/pendonor/kegiatan")
    kartu = f"//article[contains(.,'{judul}')]"

    klik(driver, By.XPATH, kartu + "//button[normalize-space()='Daftar']")
    cek_hasil(driver, "sukses", "")
    assert "Kuota tersisa: 0" in driver.find_element(By.XPATH, kartu).text

    klik(driver, By.XPATH, kartu + "//button[contains(.,'Batal')]")
    setuju_dialog(driver)
    cek_hasil(driver, "sukses", "")
    assert "Kuota tersisa: 1" in driver.find_element(By.XPATH, kartu).text


@pytest.mark.parametrize(
    "role,url,expected_type,expected_value",
    [
        # TC-01  Petugas RS tidak boleh membuka halaman pendaftaran pendonor
        ("petugas_rs", "/pendonor/kegiatan", "403", ""),
        # TC-02  Petugas RS tidak boleh mengelola kegiatan donor
        ("petugas_rs", "/admin/kegiatan-donor", "403", ""),
        # TC-03  Admin PMI tidak boleh membuka halaman pendaftaran pendonor
        ("admin_pmi", "/pendonor/kegiatan", "403", ""),
    ],
    ids=["TC-01", "TC-02", "TC-03"],
)
def test_hak_akses(driver, role, url, expected_type, expected_value):
    login(driver, role)
    buka(driver, url)
    cek_hasil(driver, expected_type, expected_value)
