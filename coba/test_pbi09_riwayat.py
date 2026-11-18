"""PBI-09 Riwayat donasi."""
from selenium.webdriver.common.by import By

from helpers import baris, galat, isi, kirim, masuk, sukses, teks_halaman, tgl, unik
from test_pbi05_pendonor import isi_pendonor


def catat(driver, tanggal, volume=350):
    form = driver.find_element(By.XPATH, "//form[contains(@action,'/donasi')][.//button[contains(.,'Simpan Donasi')]]")
    isi(driver, "tanggal_donor", tanggal, form)
    isi(driver, "volume_ml", volume, form)
    kirim(driver, "/donasi", "Simpan Donasi")


def pendonor_baru(driver):
    masuk(driver)
    isi_pendonor(driver, f"Riwayat {unik()}")
    sukses(driver)


def test_01_catat_donasi_masuk_riwayat(driver):
    pendonor_baru(driver)
    catat(driver, tgl(-120))
    assert "Donasi dicatat" in sukses(driver)
    assert "350" in teks_halaman(driver, "Riwayat")


def test_02_donasi_terlalu_dekat_dengan_donasi_sebelumnya_ditolak(driver):
    pendonor_baru(driver)
    catat(driver, tgl(-120))
    sukses(driver)
    catat(driver, tgl(-100))  # selisih 20 hari, batas minimal 60 hari
    assert galat(driver)


def test_03_hapus_donasi(driver):
    pendonor_baru(driver)
    catat(driver, tgl(-120))
    sukses(driver)
    from helpers import terima_dialog
    kirim(driver, "/donasi/", "Hapus")
    terima_dialog(driver)
    assert "Donasi dihapus" in sukses(driver)


def test_04_pendonor_melihat_riwayat_sendiri(driver):
    masuk(driver, "pendonor")
    driver.get(driver.current_url.split("/pendonor")[0] + "/pendonor/riwayat")
    assert "Riwayat" in teks_halaman(driver, "Riwayat")
    assert "/pendonor/riwayat" in driver.current_url
