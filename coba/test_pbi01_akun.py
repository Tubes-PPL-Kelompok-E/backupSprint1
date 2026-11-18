"""PBI-01 Akun pengguna dan autentikasi. Login ada di test_login.py; berkas ini mencakup registrasi, hak akses, dan kelola akun."""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from helpers import BASE_URL, WAIT, baris, buka, galat, isi, kirim, masuk, pilih, sukses, tautan, teks_halaman, unik


def daftar(driver, peran, nama, email, password, konfirmasi=None, instansi="", kode_admin=""):
    buka(driver, "/register")
    WebDriverWait(driver, WAIT).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, f"[data-role='{peran}']"))
    ).click()
    isi(driver, "name", nama)
    isi(driver, "email", email)
    if instansi:
        isi(driver, "instansi", instansi)
    if kode_admin:
        isi(driver, "kode_admin", kode_admin)
    isi(driver, "password", password)
    isi(driver, "password_confirmation", konfirmasi or password)
    kirim(driver, "/register", "Daftar Sekarang")


def test_01_registrasi_pendonor_valid(driver):
    daftar(driver, "pendonor", "Uji Selenium", f"uji.{unik().lower()}@contoh.id", "Rahasia123")
    WebDriverWait(driver, WAIT).until(EC.url_contains("/pendonor/"))  # langsung masuk ke area pendonor


def test_02_registrasi_sandi_tanpa_angka_ditolak(driver):
    daftar(driver, "pendonor", "Uji Sandi", f"sandi.{unik().lower()}@contoh.id", "HurufSaja")
    # Yang diuji: pendaftaran ditolak dan pesan galat tampil. Catatan temuan: teks pesan bawaan Laravel
    # masih berbahasa Inggris ("must contain at least one number"), berbeda dari pesan lain yang berbahasa Indonesia.
    assert galat(driver)
    assert driver.current_url.endswith("/register")


def test_03_registrasi_email_sudah_terpakai_ditolak(driver):
    daftar(driver, "pendonor", "Uji Duplikat", "pendonor@bloodconnect.id", "Rahasia123")
    assert "sudah" in galat(driver).lower()
    assert driver.current_url.endswith("/register")


def test_04_registrasi_admin_dengan_kode_salah_ditolak(driver):
    daftar(driver, "admin_pmi", "Uji Admin", f"adm.{unik().lower()}@contoh.id", "Rahasia123",
           instansi="PMI Uji", kode_admin="kode-ngawur")
    assert "kode" in galat(driver).lower()


def test_05_pendonor_tidak_boleh_membuka_halaman_admin(driver):
    masuk(driver, "pendonor")
    buka(driver, "/admin/dashboard")
    assert "403" in driver.title or "403" in driver.find_element(By.TAG_NAME, "body").text


def test_06_admin_menambah_akun_baru(driver):
    masuk(driver, "admin_pmi")
    nama = f"Petugas {unik()}"
    buka(driver, "/admin/users/create")
    isi(driver, "name", nama)
    pilih(driver, "role", "petugas_rs")
    isi(driver, "instansi", "RS Uji Selenium")
    isi(driver, "email", f"p.{unik().lower()}@contoh.id")
    isi(driver, "password", "Rahasia123")
    isi(driver, "password_confirmation", "Rahasia123")
    kirim(driver, "/admin/users", "Simpan akun")
    teks_halaman(driver, nama)
    assert nama in driver.find_element(By.TAG_NAME, "body").text
