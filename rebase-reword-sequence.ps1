# Replace "pick" with "reword" for the merge commit f51f631
$path = $args[0]
(Get-Content $path -Raw) -replace '^pick f51f631', 'reword f51f631' | Set-Content $path -NoNewline
