<?php
$tests=['Español','🇺🇸','🌐','English'];
foreach($tests as $t){
  $step1 = @iconv('UTF-8','Windows-1252//IGNORE',$t);
  $fix = @iconv('Windows-1252','UTF-8//IGNORE',$step1);
  echo "ORIG: $t\n";
  echo "FIX: $fix\n";
  echo "STEP1HEX:".bin2hex($step1)."\n";
  echo "---\n";
}
?>
