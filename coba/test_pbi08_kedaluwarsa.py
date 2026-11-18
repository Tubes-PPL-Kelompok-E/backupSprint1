"""PBI-08 Kedaluwarsa dan pemusnahan kantong.

Kantong kedaluwarsa tidak bisa dibuat lewat form (form menolak kantong yang sudah lewat masa simpan), jadi tes ini
menyisipkan satu baris langsung ke basis data. Koneksi bisa diatur lewat DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD.
"""
import os
from datetime import date, timedelta

import psycopg2
import pytest
from selenium.webdriver.common.by import By

from helpers import klik, baris, buat_kantong, buka, masuk, sukses, terima_dialog, unik


@pytest.fixture
def kantong_kedaluwarsa():
    kode = f"EXP-{unik()}"
    kini = date.today()
    sambungan = psycopg2.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"), port=os.getenv("DB_PORT", "5432"),
        dbname=os.getenv("DB_DATABASE", "bloodconnect"), user=os.getenv("DB_USERNAME", "postgres"),
        password=os.getenv("DB_PASSWORD", "root"),
    )
    with sambungan, sambungan.cursor() as c:
        c.execute(
            "INSERT INTO kantong_darah (kode_batch, tanggal_donor, volume_ml, komponen, golongan, rhesus,"
            " tanggal_kedaluwarsa, status, lokasi_simpan, created_at, updated_at)"
            " VALUES (%s, %s, 350, 'prc', 'O', 'pos', %s, 'kedaluwarsa', 'Cold Room 1', now(), now())",
            (kode, kini - timedelta(days=50), kini - timedelta(days=8)),
        )
    sambungan.close()
    return kode


def test_01_kantong_kedaluwarsa_punya_tombol_pemusnahan(driver, kantong_kedaluwarsa):
    masuk(driver)
    buka(driver, f"/admin/stok-darah?q={kantong_kedaluwarsa}")
    tr = baris(driver, kantong_kedaluwarsa)
    assert "KEDALUWARSA" in tr.text  # lencana sisa masa simpan
    assert tr.find_element(By.XPATH, ".//button[normalize-space()='Catat Pemusnahan']").is_displayed()


def test_02_catat_pemusnahan_mengubah_status_menjadi_dimusnahkan(driver, kantong_kedaluwarsa):
    masuk(driver)
    buka(driver, f"/admin/stok-darah?q={kantong_kedaluwarsa}")
    klik(driver, baris(driver, kantong_kedaluwarsa).find_element(By.XPATH, ".//button[normalize-space()='Catat Pemusnahan']"))
    terima_dialog(driver)
    assert "berhasil dicatat" in sukses(driver)
    tr = baris(driver, kantong_kedaluwarsa)
    assert "Dimusnahkan" in tr.text
    assert not tr.find_elements(By.XPATH, ".//button[normalize-space()='Catat Pemusnahan']")


def test_03_kantong_belum_kedaluwarsa_tidak_punya_tombol_pemusnahan(driver):
    masuk(driver)
    k = f"UJI-{unik()}"
    buat_kantong(driver, k)
    sukses(driver)
    buka(driver, f"/admin/stok-darah?q={k}")
    assert not baris(driver, k).find_elements(By.XPATH, ".//button[normalize-space()='Catat Pemusnahan']")
