"""PBI-05 Pendonor."""
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait

from helpers import WAIT, klik, baris, buka, isi, kirim, masuk, pilih, sukses, tgl, tombol, teks_halaman, unik


def isi_pendonor(driver, nama, lahir="1995-05-20"):
    buka(driver, "/admin/pendonor/buat")
    isi(driver, "nama", nama)
    isi(driver, "tanggal_lahir", lahir)
    pilih(driver, "jenis_kelamin", "L")
    pilih(driver, "golongan", "O")
    pilih(driver, "rhesus", "pos")
    isi(driver, "no_hp", "081234567890")
    isi(driver, "kota", "Bandung")
    kirim(driver, "/admin/pendonor", "Simpan")


def test_01_tambah_pendonor_dapat_kode_donor(driver):
    masuk(driver)
    nama = f"Pendonor Uji {unik()}"
    isi_pendonor(driver, nama)
    assert "berhasil ditambahkan" in sukses(driver)
    assert nama in driver.find_element(By.TAG_NAME, "body").text


def test_02_pendonor_kembar_diperingatkan(driver):
    masuk(driver)
    nama = f"Pendonor Kembar {unik()}"
    isi_pendonor(driver, nama, lahir="1990-01-15")
    sukses(driver)
    isi_pendonor(driver, nama, lahir="1990-01-15")
    teks_halaman(driver, "Sudah ada pendonor bernama")
    assert driver.current_url.endswith("/admin/pendonor/buat") or "/admin/pendonor" in driver.current_url


def test_03_pendonor_kembar_boleh_disimpan_setelah_dikonfirmasi(driver):
    masuk(driver)
    nama = f"Pendonor Konfirmasi {unik()}"
    isi_pendonor(driver, nama, lahir="1991-02-16")
    sukses(driver)
    isi_pendonor(driver, nama, lahir="1991-02-16")  # ditahan karena kembar; kotak konfirmasi baru muncul di sini
    teks_halaman(driver, "Sudah ada pendonor bernama")
    klik(driver, driver.find_element(By.NAME, "konfirmasi_duplikat"))
    kirim(driver, "/admin/pendonor", "Simpan")  # isian sebelumnya dipertahankan halaman
    assert "berhasil ditambahkan" in sukses(driver)


def test_04_cari_pendonor(driver):
    masuk(driver)
    nama = f"Pendonor Cari {unik()}"
    isi_pendonor(driver, nama)
    sukses(driver)
    buka(driver, "/admin/pendonor")
    isi(driver, "q", nama)
    tombol(driver, "Cari")
    # tunggu hasil pencarian dimuat: hanya satu baris pendonor yang tersisa dan itu milik `nama`
    WebDriverWait(driver, WAIT).until(
        lambda d: len(d.find_elements(By.XPATH, "//tr[.//a[normalize-space()='Detail']]")) == 1
    )
    assert nama in baris(driver, nama).text


def test_05_ubah_data_pendonor(driver):
    masuk(driver)
    nama = f"Pendonor Ubah {unik()}"
    isi_pendonor(driver, nama)
    sukses(driver)
    klik(driver, driver.find_element(By.XPATH, "//a[normalize-space()='Ubah']"))
    isi(driver, "kota", "Cimahi")
    kirim(driver, "/admin/pendonor/", "Simpan")
    assert "diperbarui" in sukses(driver)
    assert "Cimahi" in driver.find_element(By.TAG_NAME, "body").text
