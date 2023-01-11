<?php 
function accessDenied(){
    return response()->json([
      'success' => false,
      'message' => 'Access Denied!'
      ], 406);
}
?>