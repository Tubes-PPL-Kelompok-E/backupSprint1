"""PBI-09 Manajemen instansi (PMI dan rumah sakit): CRUD."""

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


def isi_instansi(driver, kode, nama, tipe, telepon, email):
    buka(driver, "/admin/instansi/create")
    isi(driver, "kode", kode)
    isi(driver, "nama", nama)
    pilih(driver, "tipe", tipe)
    isi(driver, "alamat", "Jl. Uji Coba No. 1")
    isi(driver, "kota", "Jakarta Barat")
    isi(driver, "telepon", telepon)
    isi(driver, "email", email)
    klik(driver, By.XPATH, "//button[normalize-space()='Simpan']")


@pytest.mark.parametrize(
    "kode,nama,tipe,telepon,email,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: rumah sakit dengan data lengkap
        ("AUTO", "AUTO", "rumah_sakit", "021-5684093", "info@rs.id", "url", "/admin/instansi"),
        # TC-02  EP kelas valid: PMI tanpa telepon dan email
        ("AUTO", "AUTO", "pmi", "", "", "url", "/admin/instansi"),
        # TC-03  EP kelas valid: kode dikosongkan, dibuat otomatis
        ("", "AUTO", "rumah_sakit", "", "", "url", "/admin/instansi"),
        # TC-04  EP kelas invalid: kode berisi simbol
        ("A!", "AUTO", "rumah_sakit", "", "", "error", "Kode hanya boleh huruf besar"),
        # TC-05  EP kelas invalid: kode duplikat
        ("DUPLIKAT", "AUTO", "rumah_sakit", "", "", "error", ""),
        # TC-06  EP kelas invalid: telepon berisi huruf
        ("AUTO", "AUTO", "rumah_sakit", "abc", "", "error", "Format nomor telepon tidak valid."),
        # TC-07  BVA telepon 5 karakter: di bawah batas 6
        ("AUTO", "AUTO", "rumah_sakit", "12345", "", "error", "Format nomor telepon tidak valid."),
        # TC-08  BVA telepon 6 karakter: batas bawah
        ("AUTO", "AUTO", "rumah_sakit", "123456", "", "url", "/admin/instansi"),
        # TC-09  EP kelas invalid: format email salah (ditolak validasi HTML5)
        ("AUTO", "AUTO", "rumah_sakit", "", "bukan-email", "html5_invalid", "email"),
        # TC-10  EP kelas invalid: nama kosong (ditolak validasi HTML5)
        ("AUTO", "", "rumah_sakit", "", "", "html5_required", "nama"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06", "TC-07", "TC-08", "TC-09", "TC-10"],
)
def test_instansi(driver, kode, nama, tipe, telepon, email, expected_type, expected_value):
    login(driver, "admin_pmi")
    kode_unik = "UJI-" + uuid.uuid4().hex[:6].upper()
    if nama == "AUTO":
        nama = "RS " + uniq()
    if kode == "AUTO":
        kode = kode_unik
    elif kode == "DUPLIKAT":
        kode = kode_unik
        isi_instansi(driver, kode, "RS " + uniq(), "rumah_sakit", "", "")   # buat lebih dulu
        cek_hasil(driver, "url", "/admin/instansi")
    isi_instansi(driver, kode, nama, tipe, telepon, email)
    cek_hasil(driver, expected_type, expected_value)


def test_ubah_lalu_hapus_instansi(driver):
    login(driver, "admin_pmi")
    nama, baru = "RS " + uniq(), "RS Baru " + uniq()
    isi_instansi(driver, "", nama, "rumah_sakit", "", "")
    cek_hasil(driver, "url", "/admin/instansi")

    # Ubah (EP kelas valid)
    buka(driver, f"/admin/instansi?q={nama}")
    klik(driver, By.XPATH, f"//tr[contains(.,'{nama}')]//a[contains(.,'Ubah')]")
    isi(driver, "nama", baru)
    klik(driver, By.XPATH, "//button[normalize-space()='Simpan Perubahan']")
    cek_hasil(driver, "sukses", "")

    # Hapus (soft delete)
    buka(driver, f"/admin/instansi?q={baru}")
    klik(driver, By.XPATH, f"//tr[contains(.,'{baru}')]//button[contains(.,'Hapus')]")
    setuju_dialog(driver)
    cek_hasil(driver, "sukses", "")
    buka(driver, f"/admin/instansi?q={baru}")
    cek_hasil(driver, "halaman_memuat", "Tidak ada instansi yang cocok")
