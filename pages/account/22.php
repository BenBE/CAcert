<? /*
    LibreSSL - CAcert web application
    Copyright (C) 2004-2008  CAcert Inc.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

$orgfilterid = array_key_exists('dorgfilterid',$_SESSION['_config']) ? intval($_SESSION['_config']['dorgfilterid']) : 0;
$sorting = array_key_exists('dsorting',$_SESSION['_config']) ? intval($_SESSION['_config']['dsorting']) : 0;
$status = array_key_exists('dstatus',$_SESSION['_config']) ? intval($_SESSION['_config']['dstatus']) : 0;
?>
<form method="post" action="account.php">
<table align="center" valign="middle" border="0" cellspacing="0" cellpadding="0" class="wrapper">
  <tr>
    <td colspan="8" class="title"><?=_("Organisation Server Certificates")?> </td>
  </tr>
  <tr>
    <td colspan="8" class="title"><?=_("Filter/Sorting")?></td>
  </tr>
    <tr>
      <td class="DataTD"><?=_("Organisation")?></td>
      <td colspan="7" class="DataTD" >
        <select name="dorgfilterid">
          <?=sprintf('<option value="%d"%s>%s</option>',0, 0 == $orgfilterid ? " selected" : "" ,_("All")) ?>
<?  $query = "select `orginfo`.`O`, `orginfo`.`id`
      from `org`, `orginfo`
      where `org`.`memid`='".intval($_SESSION['profile']['id'])."' and `orginfo`.`id` = `org`.`orgid`
      ORDER BY `orginfo`.`O` ";
    $reso = mysql_query($query);
    if(mysql_num_rows($reso) >= 1){
      while($row = mysql_fetch_assoc($reso)){
        printf('<option value="%d"%s>%s</option>',$row['id'], $row['id'] == $orgfilterid ? " selected" : "" , $row['O']);
      }
    }?>
        </select>
    </td>
  </tr>
  <tr>
    <td class="DataTD"><?=_("Sorting")?></td>
    <td colspan="7" class="DataTD" >
      <select name="dsorting">
        <?=sprintf('<option value="%d"%s>%s</option>',0, 0 == $sorting ? " selected" : "" ,_("expire date (desc)")) ?>
        <?=sprintf('<option value="%d"%s>%s</option>',1, 1 == $sorting ? " selected" : "" ,_("Common name, expire date (desc)")) ?>
      </select>
    </td>
  </tr>
  <tr>
    <td class="DataTD"><?=_("Certificate status")?></td>
    <td colspan="7" class="DataTD" >
      <select name="dstatus">
        <?=sprintf('<option value="%d"%s>%s</option>',0, 0 == $status ? " selected" : "" ,_("Current/Active")) ?>
        <?=sprintf('<option value="%d"%s>%s</option>',1, 1 == $status ? " selected" : "" ,_("All")) ?>
      </select>
    </td>
  </tr>
  <tr>
    <td class="DataTD" colspan="8"><input type="submit" name="reset" value="<?=_("Reset")?>" />&nbsp;&nbsp;&nbsp;&nbsp;
      <input type="submit" name="filter" value="<?=_("Apply filter/sort")?>" /></td>
  </tr>
  <tr>
    <td colspan="9" class="DataTD"> </td>
  </tr>


<?
  $query = "select UNIX_TIMESTAMP(`orgdomaincerts`.`created`) as `created`,
      UNIX_TIMESTAMP(`orgdomaincerts`.`expire`) - UNIX_TIMESTAMP() as `timeleft`,
      UNIX_TIMESTAMP(`orgdomaincerts`.`expire`) as `expired`,
      `orgdomaincerts`.`expire` as `expires`, `revoked` as `revoke`,
      UNIX_TIMESTAMP(`revoked`) as `revoked`, `CN`,
      `orgdomaincerts`.`serial`,
      `orgdomaincerts`.`id` as `id`,
      `orgdomaincerts`.`description`, `orginfo`.`O`
      from `orgdomaincerts`,`org`, `orginfo`
      where `org`.`memid`='".intval($_SESSION['profile']['id'])."'
      and `orgdomaincerts`.`orgid`=`org`.`orgid` and `orginfo`.`id` = `org`.`orgid`";

    if($orgfilterid>0)
    {
      $query .= "AND `org`.`orgid`=$orgfilterid ";
    }

    if(0==$status)
    {
      $query .= "AND `revoked`=0 AND `renewed`=0 ";
      $query .= "HAVING `timeleft` > 0 ";
    }
    switch ($sorting){
      case 0:
        $query .= "ORDER BY `orginfo`.`O`, `orgdomaincerts`.`expire` desc";
        break;
      case 1:
        $query .= "ORDER BY `orginfo`.`O`, `orgdomaincerts`.`CN`, `orgdomaincerts`.`expire` desc";
        break;
    }


//echo $query."<br>\n";
  $res = mysql_query($query);
  if(mysql_num_rows($res) <= 0)
  {
?>
  <tr>
    <td colspan="8" class="DataTD"><?=_("No domains are currently listed.")?></td>
  </tr>
<? } else {
  $orgname='';
  while($row = mysql_fetch_assoc($res))
  {
    if ($row['O']<>$orgname) {
      $orgname=$row['O'];?>
  <tr>
    <td colspan="9" class="title"></td>
  </tr>
  <tr>
    <td colspan="9" class="title"><? printf(_("Certificates for %s"), $orgname)?> </td>
  </tr>
  <tr>
    <td class="DataTD"><?=_("Renew/Revoke/Delete")?></td>
    <td class="DataTD"><?=_("Status")?></td>
    <td class="DataTD"><?=_("CommonName")?></td>
    <td class="DataTD"><?=_("SerialNumber")?></td>
    <td class="DataTD"><?=_("Revoked")?></td>
    <td class="DataTD"><?=_("Expires")?></td>
    <td colspan="2" class="DataTD"><?=_("Comment *")?></td>
  </tr>
      <?
    }
    if($row['timeleft'] > 0)
      $verified = _("Valid");
    if($row['timeleft'] < 0)
      $verified = _("Expired");
    if($row['expired'] == 0)
      $verified = _("Pending");
    if($row['revoked'] > 0)
      $verified = _("Revoked");
                if($row['revoked'] == 0)
                        $row['revoke'] = _("Not Revoked");
?>
  <tr>
<? if($verified == _("Valid") || $verified == _("Expired")) { ?>
    <td class="DataTD"><input type="checkbox" name="revokeid[]" value="<?=$row['id']?>"></td>
<? } else if($verified == _("Pending")) { ?>
    <td class="DataTD"><input type="checkbox" name="delid[]" value="<?=$row['id']?>"></td>
<? } else { ?>
    <td class="DataTD">&nbsp;</td>
<? } ?>
    <td class="DataTD"><?=$verified?></td>
    <td class="DataTD"><a href="account.php?id=23&cert=<?=$row['id']?>"><?=$row['CN']?></a></td>
    <td class="DataTD"><?=$row['serial']?></td>
    <td class="DataTD"><?=$row['revoke']?></td>
    <td class="DataTD"><?=$row['expires']?></td>
    <td class="DataTD"><input name="comment_<?=$row['id']?>" type="text" value="<?=htmlspecialchars($row['description'])?>" /></td>
    <td class="DataTD"><input type="checkbox" name="check_comment_<?=$row['id']?>" /></td>
  </tr>
<? } ?>
  <tr>
    <td class="DataTD" colspan="8">
      <?=_('* Comment is NOT included in the certificate as it is intended for your personal reference only. To change the comment tick the checkbox and hit "Change Settings".')?>
    </td>
  </tr>
  <tr>
    <td class="DataTD" colspan="6"><input type="submit" name="renew" value="<?=_("Renew")?>" />&#160;&#160;&#160;&#160;
      <input type="submit" name="revoke" value="<?=_("Revoke/Delete")?>" /></td>
    <td class="DataTD" colspan="2"><input type="submit" name="change" value="<?=_("Change settings")?>" /> </td>
  </tr>
  <tr>
    <td class="DataTD" colspan="9"><?=_("From here you can delete pending requests, or revoke valid certificates.")?></td>
  </tr>
<? } ?>
</table>
<input type="hidden" name="oldid" value="<?=$id?>" />
<input type="hidden" name="csrf" value="<?=make_csrf('orgsrvcerchange')?>" />
</form>

