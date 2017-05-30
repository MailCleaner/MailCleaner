<?php

if (!empty($_POST)):
    echo json_encode($_POST['ns']);
else: 

require_once ' Default/Model/Newsletter.php';
$newsletters = array();

?>
<table>
    <tr>
        <td>__LANG_NEWSLETTERSALLOW__</td>
        <td><input type="checkbox" class="switch" value=""></input></td>
    </tr>
</table>

<?php endif; ?>