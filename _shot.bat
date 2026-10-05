@echo off
rem TEMPORARY screenshot helper - delete after use
"C:\Program Files\Google\Chrome\Application\chrome.exe" --headless=new --disable-gpu --hide-scrollbars --user-data-dir="%TEMP%\shotprof1" --window-size=1366,2300 --screenshot="%TEMP%\rv_desktop.png" http://localhost/_shot.php?as=customer >nul 2>&1
"C:\Program Files\Google\Chrome\Application\chrome.exe" --headless=new --disable-gpu --hide-scrollbars --user-data-dir="%TEMP%\shotprof2" --window-size=1366,2300 --screenshot="%TEMP%\rv_admin.png" http://localhost/_shot.php?as=admin >nul 2>&1
"C:\Program Files\Google\Chrome\Application\chrome.exe" --headless=new --disable-gpu --user-data-dir="%TEMP%\shotprof5" --window-size=780,800 --force-device-scale-factor=2 --dump-dom http://localhost/_w.php > "%TEMP%\vw2.html" 2>nul
"C:\Program Files\Google\Chrome\Application\chrome.exe" --headless=new --disable-gpu --hide-scrollbars --user-data-dir="%TEMP%\shotprof3" --window-size=780,3400 --force-device-scale-factor=2 --screenshot="%TEMP%\rv_mobile.png" http://localhost/reviews.php >nul 2>&1
"C:\Program Files\Google\Chrome\Application\chrome.exe" --headless=new --disable-gpu --hide-scrollbars --user-data-dir="%TEMP%\shotprof4" --window-size=1366,1000 --screenshot="%TEMP%\rv_admrev.png" "http://localhost/_shot.php?as=admin&to=admin/reviews.php" >nul 2>&1
"C:\Program Files\Google\Chrome\Application\chrome.exe" --headless=new --disable-gpu --hide-scrollbars --user-data-dir="%TEMP%\shotprof6" --window-size=1366,900 --screenshot="%TEMP%\rv_lightbox.png" "http://localhost/reviews.php?lb=1" >nul 2>&1
