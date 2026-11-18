import os

import urllib.request

import pytest
from selenium import webdriver


@pytest.fixture
def driver():
    opsi = webdriver.ChromeOptions()
    if os.getenv("HEADLESS"):  # untuk server tanpa layar; di laptop biarkan kosong
        opsi.add_argument("--headless=new")
        opsi.add_argument("--no-sandbox")
    opsi.add_argument("--window-size=1400,1000")
    d = webdriver.Chrome(options=opsi)
    yield d
    d.quit()


@pytest.fixture(scope="session", autouse=True)
def aplikasi_berjalan():
    """Hentikan lebih awal dengan pesan jelas bila aplikasi belum dijalankan."""
    from helpers import BASE_URL
    try:
        urllib.request.urlopen(BASE_URL + "/login", timeout=5)
    except Exception as e:
        pytest.exit(f"Aplikasi tidak bisa dibuka di {BASE_URL}/login ({e}). Jalankan: php artisan serve", returncode=2)
