"""PBI-04 Lokasi penyimpanan kantong."""
from selenium.webdriver.common.by import By

from helpers import klik, baris, buka, galat, isi, kirim, masuk, sukses, terima_dialog, teks_halaman, unik


def tambah_lokasi(driver, nama, kapasitas=10):
    buka(driver, "/admin/lokasi-penyimpanan")
    form = driver.find_element(By.XPATH, "//form[contains(@action, '/admin/lokasi-penyimpanan') and not(contains(@action, '/admin/lokasi-penyimpanan/'))]")
    isi(driver, "nama", nama, form)
    isi(driver, "kapasitas", kapasitas, form)
    klik(driver, form.find_element(By.XPATH, ".//button[normalize-space()='Simpan']"))


def test_01_tambah_lokasi(driver):
    masuk(driver)
    nama = f"Cold {unik()}"
    tambah_lokasi(driver, nama)
    assert nama in sukses(driver)
    assert nama in driver.find_element(By.TAG_NAME, "body").text


def test_02_nama_lokasi_kembar_ditolak(driver):
    masuk(driver)
    nama = f"Cold {unik()}"
    tambah_lokasi(driver, nama)
    sukses(driver)
    tambah_lokasi(driver, nama)
    assert galat(driver)


def test_03_ubah_kapasitas_lokasi(driver):
    masuk(driver)
    nama = f"Cold {unik()}"
    tambah_lokasi(driver, nama, kapasitas=10)
    sukses(driver)
    tr = baris(driver, nama)
    tr.find_element(By.XPATH, ".//summary").click()  # form ubah ada di dalam <details>
    form = tr.find_element(By.XPATH, ".//form[contains(@action, '/admin/lokasi-penyimpanan/')][.//button[normalize-space()='Simpan']]")
    isi(driver, "kapasitas", 25, form)
    klik(driver, form.find_element(By.XPATH, ".//button[normalize-space()='Simpan']"))
    assert "diperbarui" in sukses(driver)
    assert "25" in baris(driver, nama).text


def test_04_hapus_lokasi_kosong(driver):
    masuk(driver)
    nama = f"Cold {unik()}"
    tambah_lokasi(driver, nama)
    sukses(driver)
    klik(driver, baris(driver, nama).find_element(By.XPATH, ".//button[normalize-space()='Hapus']"))
    terima_dialog(driver)
    assert "dihapus" in sukses(driver)
    assert not driver.find_elements(By.XPATH, f"//tr[contains(normalize-space(.), '{nama}')]")
