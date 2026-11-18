"""PBI-12 Pendaftaran peserta kegiatan donor (sisi pendonor)."""
import pytest
from selenium.webdriver.common.by import By

from helpers import klik, buat_kegiatan, buka, masuk, tombol, sukses, teks_halaman, terima_dialog, unik


@pytest.fixture
def kegiatan(driver):
    """Admin membuat kegiatan baru lebih dulu, lalu sesi diganti ke pendonor oleh tes."""
    masuk(driver, "admin_pmi")
    judul = f"Donor Terbuka {unik()}"
    buat_kegiatan(driver, judul)
    sukses(driver)
    return judul


def kartu(driver, judul):
    return driver.find_element(By.XPATH, f"//article[contains(normalize-space(.), '{judul}')]")


def test_01_pendonor_layak_mendaftar(driver, kegiatan):
    masuk(driver, "pendonor")
    buka(driver, "/pendonor/kegiatan")
    klik(driver, kartu(driver, kegiatan).find_element(By.XPATH, ".//button[normalize-space()='Daftar']"))
    assert "Pendaftaran berhasil" in sukses(driver)
    assert "Terdaftar" in kartu(driver, kegiatan).text


def test_02_kuota_berkurang_setelah_mendaftar(driver, kegiatan):
    masuk(driver, "pendonor")
    buka(driver, "/pendonor/kegiatan")
    assert "50 / 50" in kartu(driver, kegiatan).text
    klik(driver, kartu(driver, kegiatan).find_element(By.XPATH, ".//button[normalize-space()='Daftar']"))
    sukses(driver)
    assert "49 / 50" in kartu(driver, kegiatan).text


def test_03_batal_mendaftar_mengembalikan_kuota(driver, kegiatan):
    masuk(driver, "pendonor")
    buka(driver, "/pendonor/kegiatan")
    klik(driver, kartu(driver, kegiatan).find_element(By.XPATH, ".//button[normalize-space()='Daftar']"))
    sukses(driver)
    klik(driver, kartu(driver, kegiatan).find_element(By.XPATH, ".//form[contains(@action,'/daftar')]//button"))
    terima_dialog(driver)
    assert "dibatalkan" in sukses(driver)
    assert "50 / 50" in kartu(driver, kegiatan).text


def test_04_pendonor_belum_layak_tidak_bisa_mendaftar(driver, kegiatan):
    masuk(driver, "pendonor_belum_layak")
    buka(driver, "/pendonor/kegiatan")
    k = kartu(driver, kegiatan)
    assert "Belum layak" in k.text
    assert not k.find_elements(By.XPATH, ".//button[normalize-space()='Daftar']")


def test_05_kegiatan_ditutup_tidak_tampil_bagi_pendonor(driver, kegiatan):
    tombol(driver, "Tutup Pendaftaran")  # setelah dibuat, admin berada di halaman detail kegiatan
    sukses(driver)
    masuk(driver, "pendonor")
    buka(driver, "/pendonor/kegiatan")
    assert kegiatan not in teks_halaman(driver, "Jadwal")
