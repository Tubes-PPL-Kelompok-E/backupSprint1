import os

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
