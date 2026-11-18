"""PBI-07 Hasil uji saring dan berita acara."""
import pytest
from selenium.webdriver.common.by import By

from helpers import teks_halaman, buat_kantong, buka, galat, id_kantong, isi, kirim, masuk, radio, sukses, tgl, unik, baris


@pytest.fixture
def berkas(tmp_path):
    f = tmp_path / "berita-acara.pdf"
    f.write_bytes(b"%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n")
    return str(f)


def kantong_karantina(driver):
    masuk(driver)
    k = f"UJI-{unik()}"
    buat_kantong(driver, k, tanggal_donor=tgl(-5))
    sukses(driver)
    return k, id_kantong(driver, k)


def isi_hasil(driver, hasil, ba="BA-0001/UTD/XI/2026", file=None):
    radio(driver, "hasil_uji", hasil)
    isi(driver, "nomor_berita_acara", ba)
    if file:
        driver.find_element(By.NAME, "berkas").send_keys(file)
    kirim(driver, "/admin/uji-saring/", "Simpan Hasil")


def status_di_stok(driver, k):
    buka(driver, f"/admin/stok-darah?q={k}")
    return baris(driver, k).text


def test_01_non_reaktif_menjadi_ready(driver, berkas):
    k, kid = kantong_karantina(driver)
    buka(driver, f"/admin/uji-saring?batch={kid}")
    isi_hasil(driver, "non_reaktif", file=berkas)
    assert "Ready" in sukses(driver)
    assert "Ready" in status_di_stok(driver, k)


def test_02_reaktif_menjadi_ditahan(driver, berkas):
    k, kid = kantong_karantina(driver)
    buka(driver, f"/admin/uji-saring?batch={kid}")
    isi_hasil(driver, "reaktif", file=berkas)
    sukses(driver)
    assert "Ditahan" in status_di_stok(driver, k)


def test_03_tanpa_berkas_berita_acara_ditolak(driver):
    k, kid = kantong_karantina(driver)
    buka(driver, f"/admin/uji-saring?batch={kid}")
    isi_hasil(driver, "non_reaktif", file=None)
    assert "Berkas berita acara wajib diunggah" in teks_halaman(driver, "wajib diunggah")
    assert "Karantina" in status_di_stok(driver, k)


def test_04_format_berkas_salah_ditolak(driver, tmp_path):
    k, kid = kantong_karantina(driver)
    salah = tmp_path / "catatan.txt"
    salah.write_text("bukan pdf")
    buka(driver, f"/admin/uji-saring?batch={kid}")
    isi_hasil(driver, "non_reaktif", file=str(salah))
    assert "PDF, JPG, atau PNG" in teks_halaman(driver, "harus berformat")
