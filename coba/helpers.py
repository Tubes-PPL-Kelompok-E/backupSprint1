"""Fungsi bantu bersama untuk tes per PBI. Tidak ada time.sleep: semua menunggu lewat WebDriverWait."""
import os
import uuid
from urllib.parse import quote
from datetime import date, timedelta

from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

BASE_URL = os.getenv("BASE_URL", "http://127.0.0.1:8000")
PASSWORD = "password123"  # kata sandi semua akun contoh dari DemoSeeder
AKUN = {
    "admin_pmi": "admin.pmi@bloodconnect.id",
    "petugas_rs": "petugas.rs@bloodconnect.id",
    "pendonor": "pendonor@bloodconnect.id",          # layak mendonor
    "pendonor_belum_layak": "pendonor2@bloodconnect.id",
}
WAIT = 10

# Selektor tanpa atribut khusus, jadi aplikasi tidak perlu diubah.
# Pesan flash di layout berada langsung di dalam <main>; di halaman login/register berada di dalam <form>.
TOMBOL_SUBMIT_LOGIN = "form button:not([type='button'])"
FLASH_SUKSES = "main > div.bg-green-50.border, form div.bg-green-50.border"
FLASH_GALAT = "main > div.bg-red-50.border, form div.bg-red-50.border"


def unik(awal=""):
    """Akhiran acak supaya data tiap kali tes dijalankan tidak bentrok."""
    return awal + uuid.uuid4().hex[:6].upper()


def tgl(selisih_hari=0):
    return (date.today() + timedelta(days=selisih_hari)).isoformat()


def buka(driver, path):
    driver.get(BASE_URL + path)


def masuk(driver, kunci="admin_pmi"):
    """Login lewat halaman login. kunci: salah satu kunci AKUN. Sesi sebelumnya dibersihkan."""
    driver.delete_all_cookies()
    peran = "pendonor" if kunci.startswith("pendonor") else kunci
    buka(driver, "/login")
    WebDriverWait(driver, WAIT).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, f"[data-role='{peran}']"))
    ).click()
    driver.find_element(By.NAME, "email").send_keys(AKUN[kunci])
    driver.find_element(By.NAME, "password").send_keys(PASSWORD)
    driver.find_element(By.CSS_SELECTOR, TOMBOL_SUBMIT_LOGIN).click()
    try:
        WebDriverWait(driver, WAIT).until(lambda d: "/login" not in d.current_url)
    except TimeoutException:
        pesan = " ".join(e.text for e in driver.find_elements(By.CSS_SELECTOR, FLASH_GALAT))
        raise AssertionError(
            f"Login sebagai {kunci} gagal ({AKUN[kunci]}). Pesan halaman: {pesan or '(tidak ada)'}. "
            "Pastikan database terisi (php artisan migrate:fresh --seed)."
        )


def isi(driver, name, nilai, konteks=None):
    """Isi input/textarea bernama `name`. Untuk type=date dipakai JS agar tidak bergantung pada locale browser."""
    el = (konteks or driver).find_element(By.NAME, name)
    if el.get_attribute("type") in ("date", "time"):
        driver.execute_script(
            "arguments[0].value = arguments[1];"
            "arguments[0].dispatchEvent(new Event('input', {bubbles: true}));"
            "arguments[0].dispatchEvent(new Event('change', {bubbles: true}));",
            el, str(nilai),
        )
    else:
        el.clear()
        el.send_keys(str(nilai))


def pilih(driver, name, value, konteks=None):
    Select((konteks or driver).find_element(By.NAME, name)).select_by_value(value)


def radio(driver, name, value):
    """Radio pada form ini disembunyikan (sr-only) dan dipilih lewat label, jadi diklik dengan JS."""
    el = driver.find_element(By.CSS_SELECTOR, f"input[type='radio'][name='{name}'][value='{value}']")
    driver.execute_script("arguments[0].click();", el)


def klik(driver, el):
    """Klik elemen setelah membuang pesan flash di halaman lama, supaya sukses()/galat() hanya membaca pesan dari halaman baru."""
    driver.execute_script("document.querySelectorAll(arguments[0]).forEach(e => e.remove());", FLASH_SUKSES + ", " + FLASH_GALAT)
    el.click()


def kirim(driver, aksi, teks=None):
    """Klik tombol submit pada form yang action-nya memuat `aksi` (opsional: dengan teks tombol tertentu)."""
    xp = f"//form[contains(@action, '{aksi}')]//button"
    if teks:
        xp += f"[contains(normalize-space(.), '{teks}')]"
    tombol = WebDriverWait(driver, WAIT).until(EC.element_to_be_clickable((By.XPATH, xp)))
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", tombol)
    klik(driver, tombol)


def tombol(driver, teks):
    el = WebDriverWait(driver, WAIT).until(
        EC.element_to_be_clickable((By.XPATH, f"//button[contains(normalize-space(.), '{teks}')]"))
    )
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
    klik(driver, el)


def tautan(driver, teks):
    WebDriverWait(driver, WAIT).until(
        EC.element_to_be_clickable((By.XPATH, f"//a[contains(normalize-space(.), '{teks}')]"))
    ).click()


def pantau_ajax(driver):
    """Hitung request fetch yang masih berjalan; dipakai bersama tunggu_ajax sebelum mengirim form yang punya pengecekan AJAX."""
    driver.execute_script(
        "window.__pending = 0; const f = window.fetch;"
        "window.fetch = (...a) => { window.__pending++; return f(...a).finally(() => window.__pending--); };"
    )


def tunggu_ajax(driver):
    WebDriverWait(driver, WAIT).until(lambda d: d.execute_script("return window.__pending === 0"))


def terima_dialog(driver):
    """Tombol dengan confirm() menampilkan dialog bawaan browser; di sini disetujui."""
    WebDriverWait(driver, WAIT).until(EC.alert_is_present()).accept()


def teks_halaman(driver, teks):
    """Tunggu sampai `teks` muncul di halaman, lalu kembalikan teks seluruh body."""
    WebDriverWait(driver, WAIT).until(EC.text_to_be_present_in_element((By.TAG_NAME, "body"), teks))
    return driver.find_element(By.TAG_NAME, "body").text


def sukses(driver):
    try:
        return WebDriverWait(driver, WAIT).until(
            EC.visibility_of_element_located((By.CSS_SELECTOR, FLASH_SUKSES))
        ).text
    except TimeoutException:  # sertakan isi halaman agar penyebab kegagalan terlihat di laporan
        raise AssertionError(f"Pesan sukses tidak muncul di {driver.current_url}. Isi halaman: "
                             + driver.find_element(By.TAG_NAME, "body").text[:600])


def galat(driver):
    return WebDriverWait(driver, WAIT).until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, FLASH_GALAT))
    ).text


def cari(driver, path, kata):
    """Buka daftar yang dipaginasi dengan kata kunci pencarian (?q=), supaya data baru pasti terlihat meski data lama menumpuk."""
    buka(driver, f"{path}?q={quote(kata)}")


def baris(driver, teks):
    """Baris tabel yang memuat `teks`."""
    return WebDriverWait(driver, WAIT).until(
        EC.presence_of_element_located((By.XPATH, f"//tr[contains(normalize-space(.), '{teks}')]"))
    )


# ---------- pembuat data lewat UI (dipakai tes PBI lain yang butuh data awal)

def buat_kantong(driver, kode, tanggal_donor=None, komponen="prc", golongan="O", rhesus="pos", volume=350):
    buka(driver, "/admin/kantong-darah/create")
    # Halaman mengisi saran kode batch lewat AJAX saat dibuka; tunggu dulu supaya tidak menimpa/bercampur dengan isian tes.
    WebDriverWait(driver, WAIT).until(
        lambda d: d.find_element(By.NAME, "kode_batch").get_attribute("value") != ""
    )
    # Hitung request fetch yang masih berjalan, supaya form baru dikirim setelah pengecekan kode batch selesai
    pantau_ajax(driver)
    # Kode batch diisi lewat JS (event input) tanpa clear(): clear() membuat halaman menganggap kode kosong dan menimpanya dengan saran.
    driver.execute_script(
        "const e = document.getElementsByName('kode_batch')[0]; e.value = arguments[0];"
        "e.dispatchEvent(new Event('input', {bubbles: true}));", kode,
    )
    isi(driver, "tanggal_donor", tanggal_donor or tgl(-1))
    isi(driver, "volume_ml", volume)
    pilih(driver, "komponen", komponen)
    radio(driver, "golongan", golongan)
    radio(driver, "rhesus", rhesus)
    tunggu_ajax(driver)
    assert driver.find_element(By.NAME, "kode_batch").get_attribute("value") == kode
    kirim(driver, "/admin/kantong-darah", "Simpan Kantong")


def buat_kegiatan(driver, judul, tanggal=None, kuota=50):
    buka(driver, "/admin/kegiatan-donor/buat")
    isi(driver, "judul", judul)
    isi(driver, "tanggal", tanggal or tgl(14))
    isi(driver, "jam_mulai", "08:00")
    isi(driver, "jam_selesai", "12:00")
    isi(driver, "nama_tempat", "Aula Uji Selenium")
    isi(driver, "kuota", kuota)
    isi(driver, "alamat", "Jl. Uji Coba No. 1")
    kirim(driver, "/admin/kegiatan-donor", "Simpan Kegiatan")


def id_kantong(driver, kode):
    """Ambil id kantong dari tautan Ubah di daftar stok (cari dengan kode batch)."""
    buka(driver, f"/admin/stok-darah?q={kode}")
    href = baris(driver, kode).find_element(By.XPATH, ".//a[normalize-space()='Ubah']").get_attribute("href")
    return href.rstrip("/").split("/")[-2]
