<?php

require_once dirname(__FILE__).'/../link.Main.php';
require_once 'linkmanager.UserAuth.php';

class LinkManger_Main {
  
  private $db;
  private $file;
  
  private $enable;
  private $table = 'link_';
  private $lt;
  
  public function __construct($file,$conf) {
    $this->db = Link_DB::getInstance();
    $this->file = $file;

    $this->lt = Link_Translate::getInstance($conf);
    $this->enable = (!empty(Link_Translate::$conf->cache) && !empty(Link_Translate::$conf->cache->usecache) && Link_Translate::$conf->cache->usecache==1);
    if (!empty(Link_Translate::$conf->cache->tablesprefix)) $this->table = Link_Translate::$conf->cache->tablesprefix;
  }
  
  public function main() {

    if (!$this->enable) return $this->noCache();
    
    $content = '';
    
    if (empty($_GET['mod'])) {
      $content .= $this->welcome();
    } else {
      switch ($_GET['mod']) {
        case 'cache': $content .= $this->cache(); break;
        case 'old': $content .= $this->old(); break;
        case 'link': $content .= $this->link(); break;
        case 'update': $content .= $this->update(); break;
        case 'delete': $content .= $this->delete(); break;
        case 'all': $content .= $this->all(); break;
        case 'sticky': $content .= $this->sticky(); break;
        default: $content .= $this->welcome();
      }
    }
    return $content;
  }
  
  public function all() {
    $c = '';
    if (!empty($_POST)) {
      if (isset($_POST['refresh'])) {
        $this->db->query('UPDATE '.$this->table.'cache SET tstamp=0');
      } elseif (isset($_POST['delete'])) {
        $this->db->query('TRUNCATE '.$this->table.'cache');
        $this->db->query('TRUNCATE '.$this->table.'oldlinks');
      }
      $c .= '<div class="succes"><p>Done.</p></div>';
    }
    $c .= '
      <h2>Force all links to update upon next hit</h2>
      <p>Upon next page hit, all links will be regenerated and if changed, old link will be moved to "oldlinks".</p>
      <form method="post" action="'.$this->file.'?mod=all">
        <input type="submit" name="refresh" value="FORCE UPDATE OF ALL LINKS" />
      </form>
      
      <h2>Start again</h2>
      <p>Delete everything - cache and oldlinks.</p>
      <form method="post" action="'.$this->file.'?mod=all">
        <input type="submit" name="delete" value="DELETE EVERYTHING AND START AGAIN" />
      </form>
    ';
    return $c;
  }
  
  private function serializedArrayToQueryString($ar) {
    $ar = Link_Func::cache2params($ar);
    $pars = Array();
    foreach ($ar as $k=>$v) {
      $pars[] = $k.'='.$v;
    }
    return implode('&amp;',$pars);
  }
  
  public function delete() {
    if (empty($_GET['lid'])) $id = 0;
    else $id = (int)$_GET['lid'];
    
    if (isset($_GET['old'])) $old = true;
    else $old = false;
    
    if (!empty($id)) {
      $q = $this->db->query('DELETE FROM '.$this->table.($old?'oldlinks':'cache').' WHERE id='.$id.' LIMIT 1');
      if (!$q || $this->db->affected_rows()==0) {
          $c = '<div class="error"><p>The link hasn\'t been deleted because it doesn\'t exist (or maybe a DB error).</p></div>';
      } else {
        $c = '<div class="succes"><p>The link has been deleted.</p></div>';
      }
    }
    $c .= $this->getBackLink();
    return $c;
  }
  
  public function sticky() {
    if (empty($_GET['lid'])) $id = 0;
    else $id = (int)$_GET['lid'];
    
    if (isset($_GET['old'])) $old = true;
    else $old = false;
    
    if (!empty($id)) {
      $q = $this->db->query('UPDATE '.$this->table.'cache SET sticky=not(sticky) WHERE id='.$id.' LIMIT 1');
      if (!$q || $this->db->affected_rows()==0) {
          $c = '<div class="error"><p>The sticky value hasn\'t been changed because the link doesn\'t exist (or maybe a DB error).</p></div>';
      } else {
        $c = '<div class="succes"><p>The sticky value has been changed.</p></div>';
      }
    }
    $c .= $this->getBackLink();
    return $c;
  }
  
  public function update() {
    $c = '';
    if (empty($_GET['lid'])) $id = 0;
    else $id = (int)$_GET['lid'];
    
    if (!empty($id)) {
      $q = $this->db->query('SELECT * FROM '.$this->table.'cache WHERE id='.$id);
      $oldlink = $this->db->fetch($q);
      if ($oldlink) {
        
        $params = Link_Func::cache2params($oldlink['params']);
        Link_Translate::params2cool($params,'',true,false,true);
        
        $q = $this->db->query('SELECT * FROM '.$this->table.'cache WHERE id='.$id);
        $newlink = $this->db->fetch($q);
        
        if ($newlink['url']==$oldlink['url']) {
          $c .= '<div class="error"><p>The link hasn\'t been changed.</p></div>';
        } else {
          $c .= '<div class="succes"><p>The link has been updated from '.$oldlink['url'].' to '.$newlink['url'].'.</p></div>';
        }
        
      } else {
        $c .= '<div class="error"><p>Link with this ID is not in the cache.</p></div>';
      }
    }
    
    $c .= $this->getBackLink();
    return $c;
  }
  
  private function getBackLink() {
    if (!empty($_GET['from'])) $from = explode(':',$_GET['from']);
    return '<p class="center"><a href="'.$this->file.(empty($from)?'':'?').(empty($from[0])?'':'mod='.$from[0].(empty($from[1])?'':'&amp;l='.$from[1])).'">&lt;&lt; Back</a></p>';
  }
  
  public function cache() {
    
    if (empty($_GET['l']))
      $let = '%';
    else
      $let = $this->db->escape($_GET['l']);
    
    $c = '<h1>Cached links</h1>';
    
    $c .= '<p class="center">';
    $c .= '<b><a href="'.$this->file.'?mod=cache">all</a></b>
    ';
    for ($i=ord('A');$i<=ord('Z');$i++) {
      $c .= '<b><a href="'.$this->file.'?mod=cache&amp;l='.strtoupper(chr($i)).'">'.strtoupper(chr($i)).'</a></b>
      ';
    }
    $c .= '</p>';
    $c .= '<form method="get" action="'.$this->file.'">
    <p class="center">
      Link starts with: <input type="text" name="l" class="a" value="'.htmlspecialchars($let).'" />
      <input type="hidden" name="mod" value="cache" />
      <input type="submit" value="Search" class="submit" />
    </p>
    </form>';
    
    $q = $this->db->query('SELECT * FROM '.$this->table.'cache WHERE url LIKE \''.strtolower($let).'%\' OR url LIKE \''.strtoupper($let).'%\' ORDER BY url');
    $num = $this->db->num_rows($q);
    if ($num>0) {
      $c .= '<p class="center">Records found: '.$num.'</p>';
      $c .= '<form method="post" action="'.$this->file.'?mod=cache">';
      $c .= '<table id="list"><tr><th class="left">Cached URI</th><th>Parameters</th><th>Cached</th><th>Last check</th><th>Sticky</th><th>Action</th>';
      while ($row = $this->db->fetch($q)) {
        $c .= '<tr>
          <td class="left">'.$row['url'].'</td>
          <td>'.$this->serializedArrayToQueryString($row['params']).'</td>
          <td>'.$row['crdatetime'].'</td>
          <td>'.$row['tstamp'].'</td>
          <td>'.($row['sticky']?'YES':'NO').'</td>
          <td class="nowrap"><a href="'.$this->file.'?mod=link&amp;lid='.$row['id'].'"><img src="img/button_edit.gif" alt="Edit" title="Edit" /></a>
              <a href="'.$this->file.'?mod=update&amp;lid='.$row['id'].'&amp;from=cache:'.$let.'"><img src="img/button_refresh.gif" alt="Update" title="Update" /></a>
              <a href="'.$this->file.'?mod=delete&amp;lid='.$row['id'].'&amp;from=cache:'.$let.'"><img src="img/button_garbage.gif" alt="Delete" title="Delete" onclick="return confirm(\'Are you sure?\');" /></a>
              <a href="'.$this->file.'?mod=sticky&amp;lid='.$row['id'].'&amp;from=cache:'.$let.'"><img src="img/button_sticky.gif" alt="Sticky on/off" title="Sticky on/off" /></a>
          </td>
        </tr>';
      }
      $c .= '</table></form>';
    } else {
      $c .= '<p>No cached links found.</p>';
    }
    return $c;
  }
  
  public function old() {
    if (empty($_GET['l']))
      $let = '%';
    else
      $let = $this->db->escape($_GET['l']);
    
    $c = '<h1>Old links</h1>';
    
    $c .= '<p class="center">';
    $c .= '<b><a href="'.$this->file.'?mod=old">all</a></b>
    ';
    for ($i=ord('A');$i<=ord('Z');$i++) {
      $c .= '<b><a href="'.$this->file.'?mod=old&amp;l='.strtoupper(chr($i)).'">'.strtoupper(chr($i)).'</a></b>
      ';
    }
    $c .= '</p>';
    $c .= '<form method="get" action="'.$this->file.'">
    <p class="center">
      Link starts with: <input type="text" name="l" class="a" value="'.htmlspecialchars($let).'" />
      <input type="hidden" name="mod" value="cache" />
      <input type="submit" value="Search" class="submit" />
    </p>
    </form>';
    
    $q = $this->db->query('SELECT o.id, o.url AS ourl, l.url AS lurl, o.tstamp FROM '.$this->table.'oldlinks AS o LEFT JOIN '.$this->table.'cache AS l ON l.id=o.link_id WHERE o.url LIKE \''.strtolower($let).'%\' OR o.url LIKE \''.strtoupper($let).'%\' ORDER BY o.url');
    
    $num = $this->db->num_rows($q);
    if ($num>0) {
      $c .= '<p class="center">Records found: '.$num.'</p>';
      $c .= '<form method="post" action="'.$this->file.'?mod=cache">';
      $c .= '<table id="list"><tr><th class="left">Old URI</th><th class="left">Cached URI</th><th>Moved to olds</th><th>Action</th>';
      while ($row = $this->db->fetch($q)) {
        $c .= '<tr>
          <td class="left">'.$row['ourl'].'</td>
          <td class="left">'.$row['lurl'].'</td>
          <td>'.$row['tstamp'].'</td>
          <td class="nowrap"><a href="'.$this->file.'?mod=delete&amp;old&amp;lid='.$row['id'].'&amp;from=old:'.$let.'"><img src="img/button_garbage.gif" alt="Delete" title="Delete" onclick="return confirm(\'Are you sure?\');" /></a>
          </td>
        </tr>';
      }
      $c .= '</table></form>';
    } else {
      $c .= '<p>No old links found.</p>';
    }
    return $c;
  }
  
  public function noCache() {
    $c = '<h1>Welcome to the CoolURIs\' project\'s LinkManager</h1>
    <p>To be able to work with this LinkManager, you have to have the cache enabled.</p>';
    return $c;
  }
  
  public function welcome() {
    $c = '<h1>Welcome to the CoolURIs\' LinkManager</h1>
    <p>This manager is part of the URI Transformer project.</p>
    <dl>
      <dt>Author:</dt>
      <dd>Jan Bednařík</dd>
      <dt>Release date:</dt>
      <dd>March, 2007</dd>
      <dt>Author contact:</dt>
      <dd><a href="mailto:info@bednarik.org">info@bednarik.org</a></dd>
      <dt>Official website:</dt>
      <dd><a href="http://uri.bednarik.org">http://uri.bednarik.org</a></dd>
    </dl>
    ';
    return $c;
  }
  
  public function link() {
    if (empty($_GET['lid'])) {
      $c = '<h1>Create new CoolURI</h1>';
      $new = true;
    } else {
      $c = '<h1>Update this CoolURI</h1>';
      $new = false;
      $id = (int)$_GET['lid'];
    }
    
    if (!$new) {
      $q = $this->db->query('SELECT * FROM '.$this->table.'cache WHERE id='.$id);
      $data = $this->db->fetch($q);
      $data['params'] = str_replace('&amp;','&',$this->serializedArrayToQueryString($data['params']));
    }
    
    if (!empty($_POST)) {
      $data = $_POST;
      $data = array_map('trim',$data);
      if (empty($data['url']) || empty($data['params'])) {
        $c .= '<div class="error"><p>You must fill all inputs.</p></div>';
      } else {
        $params = Link_Func::convertQuerystringToArray($data['params']);
        $cp = Link_Func::prepareParamsForCache($params);
        
        $ok = true;
        $olq = $this->db->query('SELECT COUNT(*) FROM '.$this->table.'cache WHERE params=\''.$cp.'\''.($new?'':' AND id<>'.$id));
        $num = $this->db->fetch_row($olq);
        if ($num[0]>0) {
          $c .= '<div class="error"><p>A different link with such parameters exists already.</p></div>';
          $ok = false;
        }
        $temp = preg_replace('~/$~','',$data['url']);
        if ($temp==$data['url']) $temp .= '/';
        $olq = $this->db->query('SELECT COUNT(*) FROM '.$this->table.'cache WHERE (url=\''.$this->db->escape($temp).'\' OR url=\''.$this->db->escape($data['url']).'\')'.($new?'':' AND id<>'.$id));
        $num = $this->db->fetch_row($olq);
        if ($num[0]>0) {
          $c .= '<div class="error"><p>A different link with such URI exists already.</p></div>';
          $ok = false;
        }
        
        if ($new && $ok) {
          $q = $this->db->query('INSERT INTO '.$this->table.'cache(url,params,sticky,crdatetime) 
                                        VALUES(\''.$this->db->escape($data['url']).'\',
                                        \''.$cp.'\',
                                        '.(!empty($data['sticky']) && $data['sticky']==1?1:0).',
                                        NOW())');
          $this->db->query('DELETE FROM '.$this->table.'oldlinks WHERE url=\''.$this->db->escape($data['url']).'\'');
          if ($q) {
            $c .= '<div class="succes"><p>The new link was saved successfully.</p></div>';
            $c .= '<p class="center"><a href="'.$this->file.'?mod=cache&l='.htmlspecialchars($data['url']).'">Show &gt;&gt;</a></p>';
            $data = Array();  
          }
          else $c .= '<div class="error"><p>Could not save the link.</p></div>';
        } elseif (!empty($id) && $ok) {
          $oldq = $this->db->query('SELECT * FROM '.$this->table.'cache WHERE id='.$id);
          $old = $this->db->fetch($oldq);
          if ($data['url']!=$old['url']) {
            $q = $this->db->query('INSERT INTO '.$this->table.'oldlinks(link_id,url) 
                                        VALUES('.$id.',
                                        \''.$old['url'].'\')');
          }
          $qq = $this->db->query('UPDATE '.$this->table.'cache SET 
                                  url=\''.$this->db->escape($data['url']).'\',
                                  params=\''.$cp.'\',
                                  sticky='.(!empty($data['sticky']) && $data['sticky']==1?1:0).'
                                  WHERE id='.$id.' LIMIT 1
                                  ');
          $this->db->query('DELETE FROM '.$this->table.'oldlinks WHERE url=\''.$this->db->escape($data['url']).'\'');
          if ($qq) {
            $c .= '<div class="succes"><p>The link was updated successfully.</p></div>';
            $c .= '<p class="center"><a href="'.$this->file.'?mod=cache&l='.htmlspecialchars($data['url']).'">Show &gt;&gt;</a></p>';
          }
          else $c .= '<div class="error"><p>Could not update the link.</p></div>';
        }
      }
    }
    
    $c .= '<form method="post" action="'.$this->file.'?mod=link'.($new?'':'&amp;lid='.$id).'">
    <fieldset>
    <legend>URI details</legend>
    <label for="url">URI:</label><br />
    <input type="text" name="url" id="url" value="'.(empty($data['url'])?'':htmlspecialchars($data['url'])).'" /><br />
    <label for="params">Parameters (query string: id=1&amp;type=2):</label><br />
    <input type="text" name="params" id="params" value="'.(empty($data['params'])?'':htmlspecialchars($data['params'])).'" /><br />
    <label for="sticky">Sticky (won\'t be updated):</label><br />
    <input type="checkbox" class="check" name="sticky" id="sticky" value="1" '.(empty($data['sticky'])?'':' checked="checked"').'" />
    </fieldset>
    <input type="submit" value=" '.($new?'Save new URI':'Update this URI').' " class="submit" />
    </form>
    ';
    return $c;
  }
  
  public function menu() {
    if ($this->enable)
      $mods = Array(''=>'Home','cache'=>'Cached links','old'=>'Old links','link'=>'New link','all'=>'Delete/Update all');
    else 
      $mods = Array(''=>'Home');
    $cm = '';
    if (!empty($_GET['mod'])) $cm = $_GET['mod'];
    $c = '<ul>';
    foreach ($mods as $k=>$v) {
      $c .= '<li><a href="'.$this->file.($k?'?mod='.$k:'').'"'.($cm==$k?' class="act"':'').'>'.$v.'</a></li>';
    }
    $c .= '</ul>';
    return $c;
  }

}

?>
