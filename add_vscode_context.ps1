$codePath = "C:\Users\itart\AppData\Local\Programs\Microsoft VS Code\bin\code.exe"

# For files
New-Item -Path "Registry::HKEY_CLASSES_ROOT\*\shell\VSCode" -Force
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\*\shell\VSCode" -Name "(Default)" -Value "Open with Code"
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\*\shell\VSCode" -Name "Icon" -Value $codePath
New-Item -Path "Registry::HKEY_CLASSES_ROOT\*\shell\VSCode\command" -Force
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\*\shell\VSCode\command" -Name "(Default)" -Value "`"$codePath`" `"%1`""

# For directories
New-Item -Path "Registry::HKEY_CLASSES_ROOT\Directory\shell\VSCode" -Force
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\Directory\shell\VSCode" -Name "(Default)" -Value "Open with Code"
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\Directory\shell\VSCode" -Name "Icon" -Value $codePath
New-Item -Path "Registry::HKEY_CLASSES_ROOT\Directory\shell\VSCode\command" -Force
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\Directory\shell\VSCode\command" -Name "(Default)" -Value "`"$codePath`" `"%1`""

# For directory background
New-Item -Path "Registry::HKEY_CLASSES_ROOT\Directory\Background\shell\VSCode" -Force
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\Directory\Background\shell\VSCode" -Name "(Default)" -Value "Open with Code"
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\Directory\Background\shell\VSCode" -Name "Icon" -Value $codePath
New-Item -Path "Registry::HKEY_CLASSES_ROOT\Directory\Background\shell\VSCode\command" -Force
Set-ItemProperty -Path "Registry::HKEY_CLASSES_ROOT\Directory\Background\shell\VSCode\command" -Name "(Default)" -Value "`"$codePath`" `"%V`""