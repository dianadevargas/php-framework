<?php
   if (isset($vars->callback) && !empty($vars->callback))
   {
       echo $vars->callback . '(' .json_encode($vars->json). ')';
   }
   else 
   {
       echo json_encode($vars->json);
   }
?>