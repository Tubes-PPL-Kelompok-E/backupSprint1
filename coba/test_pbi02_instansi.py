"""PBI-02 Instansi (PMI dan rumah sakit)."""
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait

from helpers import WAIT, cari, klik, baris, buka, galat, isi, kirim, masuk, pilih, sukses, tombol, tautan, terima_dialog, teks_halaman, unik


def isi_instansi(driver, nama, kode="", kota="Bandung"):
    buka(driver, "/admin/instansi/create")
    if kode:
        isi(driver, "kode", kode)
    isi(driver, "nama", nama)
    pilih(driver, "tipe", "rumah_sakit")
    isi(driver, "alamat", "Jl. Uji Coba No. 2")
    isi(driver, "kota", kota)
    kirim(driver, "/admin/instansi")


def test_01_tambah_instansi_kode_otomatis(driver):
    masuk(driver)
    nama = f"RS Uji {unik()}"
    isi_instansi(driver, nama)
    sukses(driver)
    cari(driver, "/admin/instansi", nama)
    assert "RS-" in baris(driver, nama).text  # kode dibuat otomatis berawalan RS-


def test_02_kode_instansi_kembar_ditolak(driver):
    masuk(driver)
    kode = f"RS-{unik()}"
    isi_instansi(driver, f"RS Pertama {unik()}", kode=kode)
    sukses(driver)
    isi_instansi(driver, f"RS Kedua {unik()}", kode=kode)
    assert galat(driver)
    assert driver.current_url.endswith("/admin/instansi/create") or "/admin/instansi" in driver.current_url


def test_03_cari_instansi(driver):
    masuk(driver)
    nama = f"RS Cari {unik()}"
    isi_instansi(driver, nama)
    sukses(driver)
    buka(driver, "/admin/instansi")
    isi(driver, "q", nama)
    tombol(driver, "Cari")
    # tunggu hasil pencarian dimuat: hanya satu baris tersisa dan itu milik `nama`
    WebDriverWait(driver, WAIT).until(lambda d: len(d.find_elements(By.XPATH, "//tbody/tr")) == 1)
    assert nama in baris(driver, nama).text


def test_04_ubah_instansi(driver):
    masuk(driver)
    nama = f"RS Ubah {unik()}"
    isi_instansi(driver, nama)
    sukses(driver)
    baru = f"{nama} Baru"
    cari(driver, "/admin/instansi", nama)
    klik(driver, baris(driver, nama).find_element(By.XPATH, ".//a[normalize-space()='Ubah']"))
    isi(driver, "nama", baru)
    kirim(driver, "/admin/instansi/", "Simpan")
    sukses(driver)
    cari(driver, "/admin/instansi", baru)
    baris(driver, baru)


def test_05_hapus_instansi(driver):
    masuk(driver)
    nama = f"RS Hapus {unik()}"
    isi_instansi(driver, nama)
    sukses(driver)
    cari(driver, "/admin/instansi", nama)
    klik(driver, baris(driver, nama).find_element(By.XPATH, ".//button[normalize-space()='Hapus']"))
    terima_dialog(driver)
    sukses(driver)
    assert not driver.find_elements(By.XPATH, f"//tr[contains(normalize-space(.), '{nama}')]")
