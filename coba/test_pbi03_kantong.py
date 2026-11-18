"""PBI-03 Kantong darah: input, daftar stok, ubah, hapus."""
from selenium.webdriver.common.by import By

from helpers import klik, baris, buat_kantong, buka, galat, isi, kirim, masuk, pantau_ajax, sukses, terima_dialog, tgl, tunggu_ajax, unik


def kode():
    return f"UJI-{unik()}"


def test_01_input_kantong_valid_berstatus_karantina(driver):
    masuk(driver)
    k = kode()
    buat_kantong(driver, k)
    assert k in sukses(driver)
    buka(driver, f"/admin/stok-darah?q={k}")
    assert "Karantina" in baris(driver, k).text


def test_02_kode_batch_kembar_ditolak(driver):
    masuk(driver)
    k = kode()
    buat_kantong(driver, k)
    sukses(driver)
    buat_kantong(driver, k)
    assert "sudah dipakai" in galat(driver)


def test_03_kantong_lewat_masa_simpan_ditolak(driver):
    masuk(driver)
    buat_kantong(driver, kode(), tanggal_donor=tgl(-400))  # PRC hanya 42 hari
    assert "masa simpan" in galat(driver)


def test_04_ubah_kantong_karantina(driver):
    masuk(driver)
    k = kode()
    buat_kantong(driver, k, volume=350)
    sukses(driver)
    buka(driver, f"/admin/stok-darah?q={k}")
    klik(driver, baris(driver, k).find_element(By.XPATH, ".//a[normalize-space()='Ubah']"))
    pantau_ajax(driver)
    isi(driver, "volume_ml", 450)
    tunggu_ajax(driver)  # pengecekan kode batch harus selesai sebelum form dikirim
    kirim(driver, "/admin/kantong-darah/", "Simpan Perubahan")
    assert "diperbarui" in sukses(driver)
    buka(driver, f"/admin/stok-darah?q={k}")
    assert "450" in baris(driver, k).text


def test_05_hapus_kantong_karantina(driver):
    masuk(driver)
    k = kode()
    buat_kantong(driver, k)
    sukses(driver)
    buka(driver, f"/admin/stok-darah?q={k}")
    klik(driver, baris(driver, k).find_element(By.XPATH, ".//button[normalize-space()='Hapus']"))
    terima_dialog(driver)
    assert "dihapus" in sukses(driver)
    assert not driver.find_elements(By.XPATH, f"//tr[contains(normalize-space(.), '{k}')]")
