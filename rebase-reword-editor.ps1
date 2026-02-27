# Write new commit message to the file git provides (first argument is the path)
$msgPath = $args[0]
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$newMsg = Get-Content (Join-Path $scriptDir "rebase-reword-message.txt") -Raw
Set-Content -Path $msgPath -Value $newMsg.TrimEnd() -NoNewline
