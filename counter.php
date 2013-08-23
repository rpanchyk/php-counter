<?php

#region Constants
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('DOT', '.');

define('ROOT_DIR', dirname(__FILE__));
define('SITE_DIR', ROOT_DIR . DS . DOT . DOT);
define('DATA_DIR', 'data');
#endregion

#region FireTrot
class FireTrot
{

}
#endregion

#region FireTrotCounter
class FireTrotCounter extends FireTrot
{
	private $m_bIsDebug;
	private $m_strLogPath;
	private $m_dbConnectionString;
	private $m_dbConn;
	
	private $m_aInfo = array();
	private $m_aResult = array();
	
	private $m_aTimeFormat = array('Hours' => 'Y-m-d H:00:00', 'Days' => 'Y-m-d 00:00:00', 'Months' => 'Y-m-01 00:00:00', 'Years' => 'Y-01-01 00:00:00');
	private $m_aTimeDiff = array('Hours' => '-24 hours', 'Days' => '-30 days', 'Months' => '-12 months', 'Years' => '-10 years'); // for stat
	
	public $dateStart;
	public $dateEnd;
	
	public function __construct()
	{
		$this->InitSettings();
	}
	
	private function InitSettings()
	{
		try
		{
			// Settings
			
			// General
			$this->m_bIsDebug = false; // [ true | false ]
			$this->m_strLogPath = ROOT_DIR . DS . DATA_DIR . DS . 'exception.log';
			
			// Database
			$this->m_dbConnectionString = 'sqlite:' . ROOT_DIR . DS . DATA_DIR . DS . 'ftcounter.sqlite';
			$this->m_dbConn = null;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	
	private function DBConnect()
	{
		try
		{
			// Connect to database
			if (is_null($this->m_dbConn))
				$this->m_dbConn = new PDO($this->m_dbConnectionString);
			
			// Set error handling
			$this->m_dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function DBClose()
	{
		try
		{
			// Close database connection
			$this->m_dbConn = null;
		}
		catch (PDOException $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	
	private function GetUserInfo()
	{
		try
		{
			// Get data
			
			$this->m_aInfo['_date_create'] = date('Y-m-d H:i:s', time());
			$this->m_aInfo['remote_addr'] = $_SERVER['REMOTE_ADDR'];
			$this->m_aInfo['remote_port'] = $_SERVER['REMOTE_PORT'];
			$this->m_aInfo['http_host'] = $_SERVER['HTTP_HOST'];
			$this->m_aInfo['request_uri'] = !is_null($_REQUEST['u']) ? urldecode(base64_decode($_REQUEST['u'])) : $_SERVER['REQUEST_URI'];
			$this->m_aInfo['http_referer'] = !is_null($_REQUEST['r']) ? urldecode(base64_decode($_REQUEST['r'])) : $_SERVER['HTTP_REFERER'];
			$this->m_aInfo['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$this->m_aInfo['request_method'] = $_SERVER['REQUEST_METHOD'];
			$this->m_aInfo['query_string'] = $_SERVER['QUERY_STRING'];
			$this->m_aInfo['request_time'] = $_SERVER['REQUEST_TIME'];
			$this->m_aInfo['http_accept'] = $_SERVER['HTTP_ACCEPT'];
			$this->m_aInfo['http_accept_charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
			$this->m_aInfo['http_accept_encoding'] = $_SERVER['HTTP_ACCEPT_ENCODING'];
			$this->m_aInfo['http_accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$this->m_aInfo['http_connection'] = $_SERVER['HTTP_CONNECTION'];
			$this->m_aInfo['http_cookie'] = $_SERVER['HTTP_COOKIE'];
			$this->m_aInfo['http_keep_alive'] = $_SERVER['HTTP_KEEP_ALIVE'];
			
			foreach ( $_SERVER as $key => $value )
				if (FTUtils::StartsWith($key, 'http_') && !array_key_exists(strtolower($key), $this->m_aInfo))
					$this->m_aInfo['http_other'] .= '[' . $key . '=' . $value . '],';
			$this->m_aInfo['http_other'] = trim($this->m_aInfo['http_other'], ',');
			
			$aUrl = explode('?', $this->m_aInfo['request_uri']);
			$this->m_aInfo['page_url'] = $aUrl[0];
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function SaveUserInfo()
	{
		try
		{
			// Save data
			
			$this->DBConnect();
			
			// Save data
			if (is_array($this->m_aInfo) && count($this->m_aInfo) > 0)
			{
				$strFieldNames = '';
				$strFieldValues = '';
				
				foreach($this->m_aInfo as $k => $v)
				{
					$strFieldNames .= $k . ',';
					$strFieldValues .= '\'' . $v . '\'' . ',';
				}
				
				$st = $this->m_dbConn->prepare('INSERT INTO tData (' . trim($strFieldNames, ',') . ') VALUES (' . trim($strFieldValues, ',') . ')');
				$st->execute();
			}
			
			$this->DBClose();
		}
		catch (PDOException $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	
	private function GetData()
	{
		try
		{
			// Get data
			
			$strRestictionToday = '_date_create >= \'' . date('Y-m-d 00:00:00', time()) .  '\' AND _date_create <= \'' . date('Y-m-d 23:59:59', time()) . '\'';
			
			$this->DBConnect();
			
			// Online (количество уникальных посетителей онлайн)
			$strRestictionOnline = '_date_create >= \'' . date('Y-m-d H:i:s', strtotime("-10 minutes")) .  '\' AND _date_create <= \'' . date('Y-m-d H:i:s', time()) . '\'';;
			$data = $this->m_dbConn->query('SELECT COUNT(DISTINCT remote_addr) AS hosts_count FROM tData WHERE ' . $strRestictionOnline)->fetchAll(PDO::FETCH_ASSOC);
			$this->m_aResult['online'] = !is_null($data[0]['hosts_count']) ? $data[0]['hosts_count'] : 0;
			
			// Hits (количество загрузок за день)
			$data = $this->m_dbConn->query('SELECT COUNT(*) FROM tData WHERE ' . $strRestictionToday)->fetchAll(PDO::FETCH_NUM);
			$this->m_aResult['hits'] = is_numeric($data[0][0]) ? $data[0][0] : 0;
			
			// Hosts (количество уникальных посетителей за день)
			$data = $this->m_dbConn->query('SELECT DISTINCT remote_addr FROM tData WHERE ' . $strRestictionToday)->fetchAll(PDO::FETCH_NUM);
			$this->m_aResult['hosts'] = is_array($data) ? count($data) : 0;
			
			// Total (количество загрузок за все время)
			$data = $this->m_dbConn->query('SELECT COUNT(*) AS total FROM tData')->fetchAll(PDO::FETCH_ASSOC);
			$this->m_aResult['total'] = is_numeric($data[0]['total']) ? $data[0]['total'] : 0;
			
			$this->DBClose();
		}
		catch (PDOException $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function OutData($imageName, $aColor, $bIsTransparent, $aOnline, $aHits, $aHosts, $aTotal)
	{
		// Input example: $aOnline = array('font'=>'', 'left'=>'', 'top'=>'')
		
		try
		{
			// Draw image
			
			$strOnline = is_numeric($this->m_aResult['online']) ? $this->m_aResult['online'] : '0';
			$strHits = is_numeric($this->m_aResult['hits']) ? $this->m_aResult['hits'] : '0';
			$strHosts = is_numeric($this->m_aResult['hosts']) ? $this->m_aResult['hosts'] : '0';
			$strTotal = is_numeric($this->m_aResult['total']) ? $this->m_aResult['total'] : '0';
			
			header('Content-type: image/png');
			$im = imagecreatefrompng(ROOT_DIR . DS . 'template' . DS . (!empty($imageName) ? $imageName : 'default.png'));
			
			if ($bIsTransparent)
			{
				imagealphablending($im, true);
				imagesavealpha($im, true);
			}
			
			$R = (is_array($aColor) && count($aColor) >= 3 && !empty($aColor[0])) ? $aColor[0] : 0;
			$G = (is_array($aColor) && count($aColor) >= 3 && !empty($aColor[1])) ? $aColor[1] : 0;
			$B = (is_array($aColor) && count($aColor) >= 3 && !empty($aColor[2])) ? $aColor[2] : 0;
			
			$colorText = imagecolorallocate($im, $R, $G, $B);
			$imagesx = imagesx($im);
			
			// online
			if (!is_null($aOnline))
			{
				$font = (is_array($aOnline) && !is_null($aOnline['font']) && !empty($aOnline['font'])) ? $aOnline['font'] : 2;
				$left = (is_array($aOnline) && !is_null($aOnline['left']) && !empty($aOnline['left'])) ? $aOnline['left'] : ($imagesx - 2 - strlen($strOnline) * 7.5);
				$top = (is_array($aOnline) && !is_null($aOnline['top']) && !empty($aOnline['top'])) ? $aOnline['top'] : 17;
				imagestring($im, $font, $left, $top, $strOnline, $colorText);
			}
			
			// hits
			if (!is_null($aHits))
			{
				$font = (is_array($aHits) && !is_null($aHits['font']) && !empty($aHits['font'])) ? $aHits['font'] : 2;
				$left = (is_array($aHits) && !is_null($aHits['left']) && !empty($aHits['left'])) ? $aHits['left'] : ($imagesx - 2 - strlen($strHits) * 6.5);
				$top = (is_array($aHits) && !is_null($aHits['top']) && !empty($aHits['top'])) ? $aHits['top'] : 32;
				imagestring($im, $font, $left, $top, $strHits, $colorText);
			}
			
			// hosts
			if (!is_null($aHosts))
			{
				$font = (is_array($aHosts) && !is_null($aHosts['font']) && !empty($aHosts['font'])) ? $aHosts['font'] : 2;
				$left = (is_array($aHosts) && !is_null($aHosts['left']) && !empty($aHosts['left'])) ? $aHosts['left'] : ($imagesx - 2 - strlen($strHosts) * 6.8);
				$top = (is_array($aHosts) && !is_null($aHosts['top']) && !empty($aHosts['top'])) ? $aHosts['top'] : 47;
				imagestring($im, $font, $left, $top, $strHosts, $colorText);
			}
			
			// total
			if (!is_null($aTotal))
			{
				$font = (is_array($aTotal) && !is_null($aTotal['font']) && !empty($aTotal['font'])) ? $aTotal['font'] : 2;
				$left = (is_array($aTotal) && !is_null($aTotal['left']) && !empty($aTotal['left'])) ? $aTotal['left'] : ($imagesx - 2 - strlen($strTotal) * 6.5);
				$top = (is_array($aTotal) && !is_null($aTotal['top']) && !empty($aTotal['top'])) ? $aTotal['top'] : 62;
				imagestring($im, $font, $left, $top, $strTotal, $colorText);
			}
			
			imagepng($im);
			imagedestroy($im);
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	
	private function ProcessStatatistics()
	{
		try
		{
			// Process stat data
			
			$this->DBConnect();
			$this->m_dbConn->beginTransaction();
			
			// Collect statistics
			foreach ($this->m_aTimeFormat as $type => $timeFormat)
			{
				$data = $this->m_dbConn->query('SELECT MAX(date_end) FROM tStat' . $type . ' LIMIT 1')->fetchAll(PDO::FETCH_COLUMN);
				
				if (!is_null($data[0]))
				{
					$dtStart = date('Y-m-d H:i:s', strtotime('+1 seconds', strtotime($data[0])));
				}
				else
				{
					$dataMin = $this->m_dbConn->query('SELECT MIN(_date_create) FROM tData LIMIT 1')->fetchAll(PDO::FETCH_COLUMN);
					$dtStart = date($timeFormat, strtotime($dataMin[0]));
				}
				
				$dtEnd = date($timeFormat, time());
				while ($dtStart < $dtEnd)
				{
					$dtStartTemp = date($timeFormat, strtotime('+1 ' . $type, strtotime($dtStart))); // for use later
					$dtEndPeriod = date('Y-m-d H:i:s', strtotime('-1 seconds', strtotime($dtStartTemp))); // end of local period
					
					$dataStat = $this->m_dbConn->query('SELECT COUNT(*) AS hits_count, COUNT(DISTINCT remote_addr) AS hosts_count FROM tData WHERE _date_create >= \'' . $dtStart . '\' AND _date_create <= \'' . $dtEndPeriod . '\'')->fetchAll(PDO::FETCH_ASSOC);
					if (!is_null($dataStat[0]['hits_count']) && !is_null($dataStat[0]['hosts_count']))
					{
						// Stat hits-hosts
						$st = $this->m_dbConn->prepare('INSERT INTO tStat' . $type . ' (_date_create, date_start, date_end, hits, hosts) VALUES (\'' . date('Y-m-d H:i:s', time()) . '\', \'' . date('Y-m-d H:i:s', strtotime($dtStart)) . '\', \'' . date('Y-m-d H:i:s', strtotime($dtEndPeriod)) . '\', :hits, :hosts)');
						$st->bindParam(':hits', $dataStat[0]['hits_count'], PDO::PARAM_INT);
						$st->bindParam(':hosts', $dataStat[0]['hosts_count'], PDO::PARAM_INT);
						$st->execute();
						
						// Stat by IP (Hits, UserAgent, OS, Country)
						$dataParent = $this->m_dbConn->query('SELECT _id FROM tStat' . $type . ' WHERE date_start >= \'' . $dtStart . '\' AND date_end <= \'' . $dtEndPeriod . '\' ORDER BY date_end DESC LIMIT 1')->fetchAll(PDO::FETCH_ASSOC);
						$parent_id = (count($dataParent) <= 0 || is_null($dataParent[0]['_id'])) ? $this->m_dbConn->lastInsertId() : $dataParent[0]['_id'];
						$dataIP = $this->m_dbConn->query('SELECT *, COUNT(*) as hits_count FROM tData WHERE _date_create >= \'' . $dtStart . '\' AND _date_create <= \'' . $dtEndPeriod . '\' GROUP BY remote_addr')->fetchAll(PDO::FETCH_ASSOC);
						if (count($dataIP) > 0)
						{
							foreach ($dataIP as $rowIP)
							{
								$uagent = $this->GetUserAgent($rowIP['http_user_agent']);
								
								$stIP = $this->m_dbConn->prepare('INSERT INTO tIpStat' . $type . ' (_date_create, _parent_id, ip, hits, user_agent, user_agent_version, os, country) 
									VALUES (
										\'' . date('Y-m-d H:i:s', time()) . '\', 
										' . $parent_id . ', 
										\'' . $rowIP['remote_addr'] . '\', 
										' . intval($rowIP['hits_count']) . ', 
										\'' . (!is_null($uagent[0]) ? $uagent[0] : '') . '\', 
										\'' . (!is_null($uagent[1]) ? $uagent[1] : '') . '\', 
										\'' . $this->GetOS($rowIP['http_user_agent']) . '\', 
										\'' . $this->GetCountry($rowIP['remote_addr']) . '\'
									)');
								$stIP->execute();
							}
						}
					}
					
					$dtStart = $dtStartTemp;
				}
			}
			
			$this->m_dbConn->commit();
			$this->DBClose();
		}
		catch (PDOException $e)
		{
			$this->m_dbConn->rollBack();
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			$this->m_dbConn->rollBack();
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function GetDataStat($aData, $statType)
	{
		try
		{
			$result = '';
			
			// Check params
			if (empty($statType))
				return $result;
			
			// Check for apply datetime mask
			$bIsApplyDateTimeMask = false;
			if (is_array($aData) && count($aData) > 0)
			{
				$dtMin = $aData[0]["date_start"];
				$dtMax = $aData[count($aData) - 1]["date_start"];
				
				$dtDiff = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', strtotime($this->m_aTimeDiff[$statType], strtotime($dtMax)))));
				if ($dtMin >= $dtDiff)
					$bIsApplyDateTimeMask = true;
			}
			
			$aParamsGraph = array('numdivlines'=>'9', 'lineThickness'=>'2', 'showValues'=>'0', 
				'formatNumberScale'=>'1', 'rotateNames'=>'1', 'decimalPrecision'=>'0', 'anchorRadius'=>'3', 
				'anchorBgAlpha'=>'100', 'numberPrefix'=>'', 'divLineAlpha'=>'30', 'showAlternateHGridColor'=>'1', 
				'yAxisMinValue'=>'0', 'shadowAlpha'=>'50', 'yAxisName'=>'Units', 'numVDivLines'=>'10',
				'caption'=>'Hits - Hosts ' . $statType, 'xAxisName'=>$statType);
			
			$aParamsGraph['numVDivLines'] = (is_array($aData) && count($aData) >= 2) ? count($aData) - 2 : 10;
			$aParamsGraph['rotateNames'] = $bIsApplyDateTimeMask ? '0' : '1';
			
			switch ($statType)
			{
				case 'Hours':
					$dtMask = 'H';
					//$aParamsGraph['caption'] = 'Hits - Hosts ' . $statType . ($bIsApplyDateTimeMask ? (' (' . date('Y M d', strtotime($aData[0]["date_start"])) . ')') : '');
					break;
				case 'Days':
					$dtMask = 'M d';
					//$aParamsGraph['caption'] = 'Hits - Hosts ' . $statType . ($bIsApplyDateTimeMask ? (' (' . date('Y M', strtotime($aData[0]["date_start"])) . ')') : '');
					break;
				case 'Months':
					$dtMask = 'M';
					//$aParamsGraph['caption'] = 'Hits - Hosts ' . $statType . ($bIsApplyDateTimeMask ? (' (' . date('Y', strtotime($aData[0]["date_start"])) . ')') : '');
					break;
				case 'Years':
					$dtMask = 'Y';
					break;
				default:
					$dtMask = 'Y-m-d H:i:s';
					break;
			}
			
			$result .= $this->OpenTag('graph', $aParamsGraph, true);
			$result .= $this->OpenTag('categories', null, true);
			
			$category = '';
			$setHits = '';
			$setHosts = '';
			foreach ($aData as $row)
			{
				$category .= $this->OpenTag('category', array('Name'=>date($dtMask, strtotime($row['date_start']))));
				$setHits .= $this->OpenTag('set', array('Value'=>$row['hits']));
				$setHosts .= $this->OpenTag('set', array('Value'=>$row['hosts']));
			}
			
			$result .= $category;
			$result .= $this->CloseTag('categories');
			
			$result .= $this->OpenTag('dataset', array('seriesName'=>'Hits', 'color'=>'FF0000', 'anchorBorderColor'=>'FF0000'), true);
			$result .= $setHits;
			$result .= $this->CloseTag('dataset');

			$result .= $this->OpenTag('dataset', array('seriesName'=>'Hosts', 'color'=>'0000FF', 'anchorBorderColor'=>'0000FF'), true);
			$result .= $setHosts;
			$result .= $this->CloseTag('dataset');
			
			$result .= $this->CloseTag('graph');
			
			return $result;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function GetDataIpStat($aData, $statType)
	{
		try
		{
			$result = '';
			
			// Check params
			if (empty($statType))
				return $result;
			
			$result .= '<script type="text/javascript">
				$(function() {
					$("#tablesorter'.$statType.'")
						.tablesorter({widthFixed: true, widgets: [\'zebra\'], sortList: [[2,1]]})
						.tablesorterPager({container: $("#pager'.$statType.'")});
				});
			</script>';
			
			$result .= '<div>';
			
			$result .= '<table cellspacing="1" class="tablesorter" id="tablesorter'.$statType.'">';
			
			// ip, hits, user_agent, user_agent_version, os, country
			$result .= '<thead><tr style="text-align:center;">
				<th class="header">#&nbsp;&nbsp;</th>
				<th class="header">IP</th>
				<th class="header">Hits</th>
				<th class="header">User Agent</th>
				<th class="header">Version</th>
				<th class="header">OS</th>
				<th class="header">Country</th>
				</tr>
			</thead><tbody>';
			
			foreach ($aData as $rowPos => $row)
			{
				$result .= '<tr style="text-align:center;">
					<td>'.($rowPos + 1).'</td>
					<td>'.$row['ip'].'</td>
					<td>'.$row['hits'].'</td>
					<td>'.('<img src="images/icon_' . strtolower($row['user_agent']) . '.png" width="16" border="0" title="'.$row['user_agent'].'" alt="'.$row['user_agent'].'" />').'</td>
					<td>'.$row['user_agent_version'].'</td>
					<td>'.('<img src="images/icon_' . strtolower($row['os']) . '.png" width="16" border="0" title="'.$row['os'].'" alt="'.$row['os'].'" />').'</td>
					<td>'.('<img src="images/flags_16/' . strtolower($row['country']) . '.png" width="16" border="0" title="'.$row['country'].'" alt="'.$row['country'].'" />').'</td>
				</tr>';
			}
			
			$result .= '</tbody></table>';
			
			$result .= '<div id="pager'.$statType.'" class="pager" ' . ((count($aData) < 16) ? 'style="display:none;"' : '') . '>
				<form>
					<img src="js/pager/first.png" class="first"/>
					<img src="js/pager/prev.png" class="prev"/>
					<input type="text" class="pagedisplay"/>
					<img src="js/pager/next.png" class="next"/>
					<img src="js/pager/last.png" class="last"/>
					<select class="pagesize">
						<option selected="selected" value="16">16</option>
						<option value="32">32</option>
						<option value="64">64</option>
						<option value="128">128</option>
						<option value="256">256</option>
					</select>
				</form>
			</div>';
			
			$result .= '</div>';
			
			return $result;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function OpenTag($name, $aParams = null, $bIsHasChild = false)
	{
		try
		{
			$result = '';
			
			if (empty($name))
				return $result;
			
			$CRLF = "\n";
			if (strpos($_SERVER['SCRIPT_FILENAME'],':'))
				$CRLF = "\r" . $CRLF;
			
			$result .= '<' . $name;
			
			if (!is_null($aParams) && is_array($aParams) && count($aParams) > 0)
			{
				foreach ($aParams as $k => $v)
				{
					$result .= ' ' . $k . '="' . $v . '"';
				}
			}
			
			if (!$bIsHasChild)
				$result .= ' /';
			
			return $result . '>' . $CRLF;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function CloseTag($name)
	{
		try
		{
			$CRLF = "\n";
			if (strpos($_SERVER['SCRIPT_FILENAME'],':'))
				$CRLF = "\r" . $CRLF;
			
			return '</' . $name . '>' . $CRLF;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function GetUserAgent($text)
	{
		try
		{
			$agent = $text;
						
			$userAgent = array();
			$products = array();
						
			$pattern  = "([^/[:space:]]*)" . "(/([^[:space:]]*))?"."([[:space:]]*\[[a-zA-Z][a-zA-Z]\])?" . "[[:space:]]*"."(\\((([^()]|(\\([^()]*\\)))*)\\))?" . "[[:space:]]*";
						
			while(strlen($agent) > 0)
			{
				if ($l = ereg($pattern, $agent, $a))
				{
					// Agent, Version, Comment
					array_push($products, array($a[1], $a[3], $a[6]));
					$agent = substr($agent, $l);
				}
				else
					$agent = "";
			}
						
			foreach($products as $product)
			{
				switch($product[0])
				{
					case 'Firefox':
					case 'Chrome':
					case 'Netscape':
					case 'Safari':
					case 'Camino':
					case 'Mosaic':
					case 'Galeon':
					case 'Opera':
						$userAgent[0] = $product[0];
						$userAgent[1] = $product[1];
						break;
				}
			}
						
			if (count($userAgent) == 0)
			{
				// Mozilla compatible (MSIE, konqueror, etc)
				if ($products[0][0] == 'Mozilla' &&
					!strncmp($products[0][2], 'compatible;', 11))
				{
					$userAgent = array();
					if ($cl = ereg("compatible; ([^ ]*)[ /]([^;]*).*",
						$products[0][2], $ca))
					{
						$userAgent[0] = $ca[1];
						$userAgent[1] = $ca[2];
					}
					else
					{
						$userAgent[0] = $products[0][0];
						$userAgent[1] = $products[0][1];
					}
				}
				else
				{
					$userAgent = array();
					$userAgent[0] = $products[0][0];
					$userAgent[1] = $products[0][1];
				}
			}

			return $userAgent;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function GetUserAgent2($text)
	{
		$u_agent = $text;
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version= "";

		// Get the name of the useragent yes seperately and for good reason
		if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
		{
			$bname = 'Internet Explorer';
			$ub = "MSIE";
		}
		elseif(preg_match('/Firefox/i',$u_agent))
		{
			$bname = 'Mozilla Firefox';
			$ub = "Firefox";
		}
		elseif(preg_match('/Chrome/i',$u_agent))
		{
			$bname = 'Google Chrome';
			$ub = "Chrome";
		}
		elseif(preg_match('/Safari/i',$u_agent))
		{
			$bname = 'Apple Safari';
			$ub = "Safari";
		}
		elseif(preg_match('/Opera/i',$u_agent))
		{
			$bname = 'Opera';
			$ub = "Opera";
		}
		elseif(preg_match('/Netscape/i',$u_agent))
		{
			$bname = 'Netscape';
			$ub = "Netscape";
		}
		
		// Finally get the correct version number
		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) .
			')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}
		
		// See how many we have
		$i = count($matches['browser']);
		if ($i != 1) {
			//we will have two since we are not using 'other' argument yet
			//see if version is before or after the name
			if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
				$version= $matches['version'][0];
			}
			else {
				$version= $matches['version'][1];
			}
		}
		else {
			$version= $matches['version'][0];
		}
		
		// Check if we have a number
		if ($version==null || $version=="") {$version="?";}
		
		return array($bname, $version);
	}
	private function GetOS($text)
	{
		try
		{
			$os = 'unknown';
			
			if (preg_match('/windows|win32|winnt/i', $text))
				$os = 'windows';
			elseif (preg_match('/linux/i', $text))
				$os = 'linux';
			elseif (preg_match('/macintosh|mac os x/i', $text))
				$os = 'mac';
			
			return $os;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function GetCountry($ip)
	{
		try
		{
			$result = '';
			
			if (empty($ip))
				return $result;
			/*
			if (FTUtils::StartsWith('127.', $ip) || FTUtils::StartsWith('10.', $ip) || FTUtils::StartsWith('172.', $ip) || FTUtils::StartsWith('192.168.', $ip))
			{
				//10.0.0.0/8 (10.0.0.0-10.255.255.255); 1 сеть класса A
				//172.16.0.0/12 (172.16.0.0-172.31.255.255); 16 сетей класса B
				//192.168.0.0/16 (192.168.0.0-192.168.255.255); 256 сетей класса C 
				
				$intIp = $this->ConvertIpToInt($ip);
				
				if (($intIp >= $this->ConvertIpToInt('10.0.0.0') && $intIp <= $this->ConvertIpToInt('10.255.255.255')) ||
						($intIp >= $this->ConvertIpToInt('172.16.0.0') && $intIp <= $this->ConvertIpToInt('172.31.255.255')) ||
						($intIp >= $this->ConvertIpToInt('192.168.0.0') && $intIp <= $this->ConvertIpToInt('192.168.255.255')) ||
						FTUtils::StartsWith('127.', $ip))
				{
					return 'UA';
				}
			}
			*/
			
			$intIp = $this->ConvertIpToInt($ip);
			
			$Data = $this->m_dbConn->query('SELECT * FROM tGeoIp WHERE ' . $intIp . ' >= ip_int_start AND ' . $intIp . ' <= ip_int_end')->fetchAll(PDO::FETCH_ASSOC);
			if (count($Data) > 0)
				$result = $Data[0]['country_short'];
			
			return $result;
		}
		catch (PDOException $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function ConvertIpToInt($ip)
	{
		try
		{
			$part = explode('.', $ip);
			$int = 0;
			if (count($part) == 4) {
				$int = $part[3] + 256 * ($part[2] + 256 * ($part[1] + 256 * $part[0]));
			}
			
			return $int;
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	private function ConvertIntToIp($int)
	{
		try
		{
			$w = $int / 16777216 % 256;
			$x = $int / 65536 % 256;
			$y = $int / 256 % 256;
			$z = $int % 256;
			$z = $z < 0 ? $z + 256 : $z;
			
			return "$w.$x.$y.$z";
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	
	public function Install($bIsUseGeoIp = false)
	{
		try
		{
			$this->DBConnect();
			$this->m_dbConn->beginTransaction();
			
			$strInstallFilePath = ROOT_DIR . DS . 'addon' .DS . 'install.sql';
			$CRLF = "\n";
			if (strpos($_SERVER['SCRIPT_FILENAME'],':'))
				$CRLF = "\r" . $CRLF;
			
			FTUtils::WriteFile('/* FiretrotCounter install script at ' . date('Y-m-d H:i:s', time()) . ' */' . $CRLF . $CRLF, $strInstallFilePath, 'w');
			
			try
			{
				$sql = 'SELECT 1 FROM tData LIMIT 1';
				$result = $this->m_dbConn->query($sql);
				$result->closeCursor();
			}
			catch (PDOException $e)
			{
				$sql = 'CREATE TABLE tData (
					_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
					_date_create DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					remote_addr VARCHAR(15),
					remote_port VARCHAR(5),
					http_host VARCHAR(256),
					request_uri VARCHAR(1024),
					http_referer VARCHAR(1024),
					http_user_agent VARCHAR(1024),
					request_method VARCHAR(4),
					query_string VARCHAR(1024),
					request_time VARCHAR(15),
					http_accept VARCHAR(256),
					http_accept_charset VARCHAR(256),
					http_accept_encoding VARCHAR(256),
					http_accept_language VARCHAR(256),
					http_connection VARCHAR(64),
					http_cookie VARCHAR(1024),
					http_keep_alive VARCHAR(64),
					http_other VARCHAR(4096),
					page_url VARCHAR(1024)
				 );';
				FTUtils::WriteFile($sql . $CRLF . $CRLF, $strInstallFilePath);
				$st = $this->m_dbConn->prepare($sql);
				$st->execute();
				$st->closeCursor();
			}
			
			foreach ($this->m_aTimeFormat as $type => $timeFormat)
			{
				try
				{
					$sql = 'SELECT 1 FROM tStat' . $type . ' LIMIT 1';
					$result = $this->m_dbConn->query($sql);
					$result->closeCursor();
				}
				catch (PDOException $e)
				{
					$sql = 'CREATE TABLE tStat' . $type . ' (
						_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
						_date_create DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
						date_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
						date_end DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
						hits INTEGER NOT NULL DEFAULT 0,
						hosts INTEGER NOT NULL DEFAULT 0
					 );';
					FTUtils::WriteFile($sql . $CRLF . $CRLF, $strInstallFilePath);
					$st = $this->m_dbConn->prepare($sql);
					$st->execute();
					$st->closeCursor();
				}
			}
			
			foreach ($this->m_aTimeFormat as $type => $timeFormat)
			{
				try
				{
					$sql = 'SELECT 1 FROM tIpStat' . $type . ' LIMIT 1';
					$result = $this->m_dbConn->query($sql);
					$result->closeCursor();
				}
				catch (PDOException $e)
				{
					$sql = 'CREATE TABLE tIpStat' . $type . ' (
						_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
						_date_create DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
						_parent_id INTEGER NOT NULL,
						ip VARCHAR(15) NOT NULL,
						hits INTEGER NOT NULL DEFAULT 0,
						user_agent VARCHAR(64),
						user_agent_version VARCHAR(64),
						os VARCHAR(64),
						country VARCHAR(64),
						FOREIGN KEY(_parent_id) REFERENCES tStat' . $type . '(_id)
					 );';
					FTUtils::WriteFile($sql . $CRLF . $CRLF, $strInstallFilePath);
					$st = $this->m_dbConn->prepare($sql);
					$st->execute();
					$st->closeCursor();
				}
			}
			
			if ($bIsUseGeoIp)
			{
				try
				{
					$sql = 'SELECT 1 FROM tGeoIp LIMIT 1';
					$result = $this->m_dbConn->query($sql);
					$result->closeCursor();
				}
				catch (PDOException $e)
				{
					try
					{
						$sql = 'CREATE TABLE tGeoIp (
							_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
							ip_start VARCHAR(15) NOT NULL,
							ip_end VARCHAR(15) NOT NULL,
							ip_int_start INTEGER NOT NULL,
							ip_int_end INTEGER NOT NULL,
							country_short VARCHAR(2) NOT NULL,
							country_eng VARCHAR(64) NOT NULL,
							country_rus VARCHAR(128)
						);';
						FTUtils::WriteFile($sql . $CRLF . $CRLF, $strInstallFilePath);
						$st = $this->m_dbConn->prepare($sql);
						$st->execute();
						$st->closeCursor();
						
						$fp = fopen(ROOT_DIR . DS . 'addon' . DS . 'GeoIPCountryWhois.csv', 'rb');
						while ($line = fgets($fp))
						{
							$aData = explode(',', trim($line), 6);
							if (count($aData) == 6)
							{
								$sql = 'INSERT INTO tGeoIp (ip_start, ip_end, ip_int_start, ip_int_end, country_short, country_eng) VALUES (\'' . trim($aData[0], '"') . '\', \'' . trim($aData[1], '"') . '\', ' . trim($aData[2], '"') . ', ' . trim($aData[3], '"') . ', \'' . trim($aData[4], '"') . '\', \'' . str_replace('\'', '\'\'', trim($aData[5], '"')) . '\');';
								FTUtils::WriteFile($sql . $CRLF, $strInstallFilePath);
								$st = $this->m_dbConn->prepare($sql);
								$st->execute();
								$st->closeCursor();
							}
						}
						fclose($fp);
					}
					catch (PDOException $e)
					{
						throw $e;
					}
					catch (Exception $e)
					{
						throw $e;
					}
				}
			}
			
			$this->m_dbConn->commit();
			$this->DBClose();
			
			echo '<h1>Installation... OK!</h1>';
		}
		catch (PDOException $e)
		{
			$this->m_dbConn->rollBack();
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			$this->m_dbConn->rollBack();
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	public function GetCounter($imageName = '', $aColor = array(0, 0, 0), $bIsTransparent = false, $aOnline = array(), $aHits = array(), $aHosts = array(), $aTotal = array())
	{
		try
		{
			//$this->Install();
			
			$this->GetUserInfo(); //if ($this->m_bIsDebug) { FTUtils::PrintArray($this->m_aInfo); }
			$this->SaveUserInfo();
			
			$this->GetData();
			$this->OutData($imageName, $aColor, $bIsTransparent, $aOnline, $aHits, $aHosts, $aTotal);
			
			$this->ProcessStatatistics();
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
	public function GetStatatistics($statType = '', $dateStart = null, $dateEnd = null)
	{
		try
		{
			$this->ProcessStatatistics();
			
			$this->DBConnect();
			
			foreach ($this->m_aTimeFormat as $type => $timeFormat)
			{
				// Process desired stat only
				if (!empty($statType) && $statType != $type)
					continue;
				
				// Get end of period
				if (is_null($dateEnd))
					$this->dateEnd[$type] = date('Y-m-d H:i:s', strtotime('-1 seconds', strtotime(date($timeFormat, time()))));
				else
					$this->dateEnd[$type] = date('Y-m-d H:i:s', strtotime($dateEnd));
				
				// Get start of period
				if (is_null($dateStart))
					$this->dateStart[$type] = date('Y-m-d H:i:s', strtotime('+1 seconds', strtotime(date('Y-m-d H:i:s', strtotime($this->m_aTimeDiff[$type], strtotime($this->dateEnd[$type]))))));
				else
					$this->dateStart[$type] = date('Y-m-d H:i:s', strtotime($dateStart));
				
				// Get stat data
				$data = $this->m_dbConn->query('SELECT * FROM tStat' . $type . ' WHERE date_start >= \'' . $this->dateStart[$type] . '\' AND date_end <= \'' . $this->dateEnd[$type] . '\'')->fetchAll(PDO::FETCH_ASSOC);
				
				// Create graph xml files
				$filepath = ROOT_DIR . DS . DATA_DIR . DS . 'Stat' . $type . '.xml';
				FTUtils::WriteFile($this->GetDataStat($data, $type), $filepath, 'w');
				
				// Get IP stat data
				$dataIP = $this->m_dbConn->query('SELECT ips.ip AS ip, SUM(ips.hits) AS hits, ips.user_agent AS user_agent, ips.user_agent_version AS user_agent_version, ips.os AS os, ips.country AS country 
					FROM tIpStat' . $type . ' as ips 
					JOIN tStat' . $type . ' AS s ON s._id = ips._parent_id 
					WHERE s.date_start >= \'' . $this->dateStart[$type] . '\' AND s.date_end <= \'' . $this->dateEnd[$type] . '\' 
					GROUP BY ips.ip, ips.user_agent, ips.user_agent_version, ips.os, ips.country 
				')->fetchAll(PDO::FETCH_ASSOC);
				
				// Create table html files
				$filepathIP = ROOT_DIR . DS . DATA_DIR . DS . 'IpStat' . $type . '.html';
				FTUtils::WriteFile($this->GetDataIpStat($dataIP, $type), $filepathIP, 'w');
			}
			
			$this->DBClose();
		}
		catch (PDOException $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
		catch (Exception $e)
		{
			FTUtils::SaveException($e, $this->m_strLogPath);
			die($e->getMessage());
		}
	}
}
#endregion

#region FTUtils
class FTUtils extends FireTrot
{
	public static function PrintArray($data)
	{
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	}
	
	public static function StartsWith($haystack, $needle, $case = FALSE)
	{
		// http://snipplr.com/view/13213/check-if-a-string-ends-with-another-string/
		

		if ($case)
		{
			return (strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
		}
		
		return (strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
	}
	
	public static function EndsWith($haystack, $needle, $case = FALSE)
	{
		// http://snipplr.com/view/13214/check-if-a-string-begins-with-another-string/
		

		if ($case)
		{
			return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
		}
		
		return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
	}
	
	public static function SaveException($e, $filepath, $openMode = 'a')
	{
		if (!is_subclass_of($e, 'Exception'))
			return;
		
		$CRLF = "\n";
		if (strpos($_SERVER['SCRIPT_FILENAME'],':'))
			$CRLF = "\r" . $CRLF;
		
		$fp = fopen($filepath, $openMode);
		fwrite($fp, $CRLF . $CRLF . 'File: "' . $e->getFile() . ':' . $e->getLine() . '" = ' . $e->getMessage() . ' (code ' . $e->getCode() . ')' . $CRLF . str_replace("\n", $CRLF, $e->getTraceAsString()));
		fclose($fp);
	}
	
	public static function WriteFile($strText, $filepath, $openMode = 'a')
	{
		try
		{
			$fp = fopen($filepath, $openMode);
			fwrite($fp, $strText);
			fclose($fp);
		}
		catch (Exception $e)
		{
			die($e->getMessage());
		}
	}
}
#endregion

?>
