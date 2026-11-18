"""PBI-06 Pemeriksaan pra-donor dan kelayakan."""
from selenium.webdriver.common.by import By

from helpers import buka, isi, kirim, masuk, pilih, teks_halaman, unik
from test_pbi05_pendonor import isi_pendonor


def periksa(driver, hb, sistolik=120, diastolik=80, berat=60, suhu=36.6, nadi=72):
    for nama, nilai in [("hemoglobin", hb), ("sistolik", sistolik), ("diastolik", diastolik),
                        ("berat_kg", berat), ("suhu_tubuh", suhu), ("nadi", nadi)]:
        isi(driver, nama, nilai)
    kirim(driver, "/pemeriksaan", "Periksa")


def pendonor_baru(driver):
    masuk(driver)
    isi_pendonor(driver, f"Periksa {unik()}")
    teks_halaman(driver, "berhasil ditambahkan")


def test_01_nilai_normal_dinyatakan_layak(driver):
    pendonor_baru(driver)
    periksa(driver, hb=14.0)
    assert "LAYAK mendonor" in teks_halaman(driver, "Pemeriksaan selesai")


def test_02_hemoglobin_rendah_tidak_layak(driver):
    pendonor_baru(driver)
    periksa(driver, hb=9.0)
    assert "TIDAK LAYAK" in teks_halaman(driver, "Pemeriksaan selesai")


def test_03_tekanan_darah_tinggi_tidak_layak(driver):
    pendonor_baru(driver)
    periksa(driver, hb=14.0, sistolik=190, diastolik=120)
    assert "TIDAK LAYAK" in teks_halaman(driver, "Pemeriksaan selesai")


def test_04_donasi_ditolak_bila_pemeriksaan_hari_ini_tidak_layak(driver):
    pendonor_baru(driver)
    periksa(driver, hb=9.0)
    teks_halaman(driver, "TIDAK LAYAK")
    from helpers import tgl
    isi(driver, "tanggal_donor", tgl(0), driver.find_element(By.XPATH, "//form[contains(@action,'/donasi')]"))
    kirim(driver, "/donasi", "Simpan Donasi")
    assert "TIDAK LAYAK. Donasi tidak dapat dicatat" in teks_halaman(driver, "Donasi tidak dapat dicatat")
