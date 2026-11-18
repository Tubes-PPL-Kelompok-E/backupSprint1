"""PBI-10 Kegiatan donor."""
from selenium.webdriver.common.by import By

from helpers import (buat_kegiatan, buka, galat, isi, kirim, masuk, sukses, terima_dialog, teks_halaman, tgl,
                     tombol, unik)


def test_01_buat_kegiatan(driver):
    masuk(driver)
    judul = f"Donor Uji {unik()}"
    buat_kegiatan(driver, judul)
    sukses(driver)
    assert judul in teks_halaman(driver, judul)


def test_02_jam_selesai_sebelum_jam_mulai_ditolak(driver):
    masuk(driver)
    buka(driver, "/admin/kegiatan-donor/buat")
    isi(driver, "judul", f"Jam Salah {unik()}")
    isi(driver, "tanggal", tgl(10))
    isi(driver, "jam_mulai", "12:00")
    isi(driver, "jam_selesai", "08:00")
    isi(driver, "nama_tempat", "Aula")
    isi(driver, "kuota", 20)
    isi(driver, "alamat", "Jl. Uji")
    kirim(driver, "/admin/kegiatan-donor", "Simpan Kegiatan")
    assert galat(driver)


def test_03_tutup_pendaftaran(driver):
    masuk(driver)
    buat_kegiatan(driver, f"Tutup {unik()}")
    sukses(driver)
    tombol(driver, "Tutup Pendaftaran")
    sukses(driver)
    assert not driver.find_elements(By.XPATH, "//button[contains(.,'Tutup Pendaftaran')]")


def test_04_batalkan_kegiatan(driver):
    masuk(driver)
    buat_kegiatan(driver, f"Batal {unik()}")
    sukses(driver)
    tombol(driver, "Batalkan Kegiatan")
    terima_dialog(driver)
    sukses(driver)
    assert not driver.find_elements(By.XPATH, "//button[contains(.,'Batalkan Kegiatan')]")
