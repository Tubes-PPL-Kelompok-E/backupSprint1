"""PBI-11 Batas minimum stok dan dashboard."""
from selenium.webdriver.common.by import By

from helpers import FLASH_SUKSES, buka, isi, kirim, masuk, sukses, teks_halaman, terima_dialog, tombol

PERTAMA = "input[name^='batas']"


def nilai_pertama(driver):
    return driver.find_element(By.CSS_SELECTOR, PERTAMA).get_attribute("value")


def test_01_ubah_batas_minimum_tersimpan(driver):
    masuk(driver)
    buka(driver, "/admin/batas-minimum")
    asli = nilai_pertama(driver)
    baru = "37" if asli != "37" else "38"
    try:
        isi(driver, driver.find_element(By.CSS_SELECTOR, PERTAMA).get_attribute("name"), baru)
        tombol(driver, "Simpan Perubahan")
        sukses(driver)
        buka(driver, "/admin/batas-minimum")
        assert nilai_pertama(driver) == baru
    finally:  # kembalikan agar tes bisa diulang tanpa mengubah data contoh
        buka(driver, "/admin/batas-minimum")
        isi(driver, driver.find_element(By.CSS_SELECTOR, PERTAMA).get_attribute("name"), asli)
        tombol(driver, "Simpan Perubahan")
        sukses(driver)


def test_02_nilai_negatif_ditolak_browser(driver):
    masuk(driver)
    buka(driver, "/admin/batas-minimum")
    kolom = driver.find_element(By.CSS_SELECTOR, PERTAMA)
    isi(driver, kolom.get_attribute("name"), "-5")
    assert driver.execute_script("return arguments[0].validity.rangeUnderflow", kolom)
    tombol(driver, "Simpan Perubahan")
    assert driver.current_url.endswith("/admin/batas-minimum")  # form tidak terkirim
    assert not driver.find_elements(By.CSS_SELECTOR, FLASH_SUKSES)


def test_03_kembalikan_ke_nilai_bawaan(driver):
    masuk(driver)
    buka(driver, "/admin/batas-minimum")
    tombol(driver, "Kembalikan ke nilai bawaan")
    terima_dialog(driver)
    sukses(driver)


def test_04_dashboard_admin_menampilkan_stok(driver):
    masuk(driver)
    buka(driver, "/admin/dashboard")
    assert driver.find_element(By.ID, "filter-komponen").is_displayed()
    assert "Stok" in teks_halaman(driver, "Stok")


def test_05_dashboard_petugas_rs_menampilkan_stok(driver):
    masuk(driver, "petugas_rs")
    assert "/petugas/dashboard" in driver.current_url
    assert "Stok" in teks_halaman(driver, "Stok")
