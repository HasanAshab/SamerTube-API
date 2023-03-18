<?php
function accessDenied() {
  return response()->json([
    'success' => false,
    'message' => 'Access Denied!'
  ], 406);
}

function getClassByType($type){
    $className = ucfirst($type);
    $fullClassName = "App\\Models\\{$className}";
    return class_exists($fullClassName) ? $fullClassName : null;
}
?>