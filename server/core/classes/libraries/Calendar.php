<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Calendar generate library
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Calendar implements Growable
{
	/**
	 * Generate settings
	 * @var array
	 */
	protected $setting = array();
	
	
	/**
	 * Default settings
	 * @var array
	 */
	protected $_default = array(
		'today_class'     => 'today',
		'today_tag'       => 'strong',
		'sunday_class'    => 'sunday',
		'saturday_class'  => 'saturday',
		'day_class'       => 'day',
		'pastday_class'   => 'past',
		'futureday_class' => 'future',
		'calendar_class'  => 'sz_calendar',
		'weekrow_class'   => 'week',
		'next_prev_url'   => TRUE,
		'dayString'       => 'japanese'
	);
	
	
	/**
	 * Day strings
	 * @var array
	 */
	protected $dayStrings = array(
		'sun' => array('english' => 'Sun', 'japanese' => '日'),
		'mon' => array('english' => 'Mon', 'japanese' => '月'),
		'tue' => array('english' => 'Tue', 'japanese' => '火'),
		'wed' => array('english' => 'Wed', 'japanese' => '水'),
		'thu' => array('english' => 'Thu', 'japanese' => '木'),
		'fri' => array('english' => 'Fri', 'japanese' => '金'),
		'sat' => array('english' => 'Sat', 'japanese' => '土')
	);
	
	
	/**
	 * Last day pool
	 * @var array
	 */
	protected $_lastDayPool = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	
	
	public function __construct($conf = array())
	{
		$this->configure($conf);
	}
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Calendar ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Calendar');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Genetrate settings configurration
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		$this->setting = array_merge($this->_default, $conf);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate a calendar
	 * 
	 * @access public
	 * @param  mixed $year
	 * @param  mixed $month
	 * @param  array $linkData
	 * @return string
	 */
	public function generate($year = null, $month = null, $linkData = array())
	{
		$year  = ( ! $year  ) ? (int)date('Y') : (int)$year;
		$month = ( ! $month ) ? (int)date('n') : (int)$month;
		$day   = (int)date('j');
		
		// Is now leap year?
		if ( $year % 4 === 0 && ($year % 100 > 0 || $year % 400 === 0) )
		{
			$this->_lastDayPool[1] = 29; // 2/29
		}

		$last_day   = $this->_lastDayPool[$month - 1];
		$date_array = array();
		$date_state = getdate(mktime(0, 0, 0, $month, 1, $year));
		$wday       = $date_state['wday'];
		
		// padding previous month for first week
		for ( $ep = 0; $ep < $wday; $ep++ )
		{
			$date_array[] = 'p';
		}
		// insert day
		for ( $d = 0; $d < $last_day; $d++ )
		{
			$date_array[] = $d + 1;
		}
		// padding next month for last week
		$cnt = count($date_array) % 7;
		for ( $en = 0; $en < $cnt; $en++ )
		{
			$date_array[] = 'n';
		}
		
		// output start
		$out = array(
			'<table class="' . prep_str($this->setting['calendar_class']) . '" cellspacing="0" cellpadding="0" border="1">',
			'<tbody>',
			$this->_generateCaptionRow($year, $month),
			$this->_generateWeekRow()
		);
		
		// geneate day cell ------------------------------------
		$todayList = array((int)date('Y'), (int)date('n'), (int)date('j'));
		$cnt       = ceil(count($date_array) / 7);
		$currentYM = ( $todayList[0] === $year && $todayList[1] === $month ) ? TRUE : FALSE;
		
		for ( $r = 0; $r < $cnt; $r++ )
		{
			$out[] = '<tr>';
			for ( $d = 0; $d < 7; $d++ )
			{
				$cur   = $date_array[$d + ($r * 7)];
				$class = array();
				$today = FALSE;
				if ( $currentYM === TRUE && $cur == $todayList[2] )
				{
					$today = TRUE;
					$class[] = $this->setting['today_class'];
				}
				switch ( $d )
				{
					case 0:
						$class[] = $this->setting['sunday_class'];
						break;
					case 6:
						$class[] = $this->setting['saturday_class'];
						break;
					default:
						$class[] = $this->setting['day_class'];
						break; 
				}
				if ( $cur === 'p' )
				{
					$str     = '&nbsp;';
					$class[] = $this->setting['pastday_class']; 
				}
				else if ( $cur === 'n' )
				{
					$str     = '&nbsp;';
					$class[] = $this->setting['futureday_class'];
				}
				else
				{
					$str = ( isset($linkData[$cur]) ) ? '<a href="' . prep_str($linkData) . '">' : '';
					if ( $today )
					{
						$str .= '<' . prep_str($this->setting['today_tag']) . '>' . $cur . '</' . prep_str($this->setting['today_tag']) . '>'; 
					}
					else
					{
						$str .= $cur;
					}
					$str .= ( isset($linkData[$cur]) ) ? '</a>' : '';
				}
				$out[] = '<td' . (( count($class) > 0 ) ? ' class="' . implode(' ', $class) : '') . '">'
				         . $str
				         . '</td>';
			}
			$out[] = '</tr>';
		}
		$out[] = '</tbody>';
		$out[] = '</table>';
		
		return implode("\n", $out);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate year-month caption row
	 * 
	 * @access protected
	 * @param  int $y
	 * @param  int $m
	 * @return string
	 */
	protected function _generateCaptionRow($y, $m)
	{
		$row = array('<tr>');
		$str = '<th colspan="7">';
		
		if ( $this->setting['next_prev_url'] !== FALSE )
		{
			$str .= '<a href="' . prep_str(trail_slash($this->setting['next_prev_url']) . date('Y/m', mktime(0, 0, 0, $m - 1, 0, $y))) . '">&laquo;前月</a>';
		}
	
		$str .= ( $this->setting['dayString'] === 'japanese' )
		          ? sprintf('%s年%s月', (string)$y, (string)$m)
		          : sprintf('%s, %s', (string)$m, (string)$y);
		
		if ( $this->setting['next_prev_url'] !== FALSE )
		{
			$str .= '<a href="' . prep_str(trail_slash($this->setting['next_prev_url']) . date('Y/m', mktime(0, 0, 0, $m + 1, 0, $y))) . '">翌月&raquo;</a>';
		}
		
		$str .= '</th>';
		$row[] = $str;
		$row[] = '</tr>';
		
		return implode("\n", $row);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate Week row
	 * 
	 * @access protected
	 * @return string
	 */
	protected function _generateWeekRow()
	{
		$row = array(
			'<tr>',
			'<th class="' . $this->setting['weekrow_class'] . ' ' . $this->setting['sunday_class'] .'">' . $this->dayStrings['sun'][$this->setting['dayString']] . '</th>',
			'<th class="' . $this->setting['weekrow_class'] . '">' . $this->dayStrings['mon'][$this->setting['dayString']] . '</th>',
			'<th class="' . $this->setting['weekrow_class'] . '">' . $this->dayStrings['tue'][$this->setting['dayString']] . '</th>',
			'<th class="' . $this->setting['weekrow_class'] . '">' . $this->dayStrings['wed'][$this->setting['dayString']] . '</th>',
			'<th class="' . $this->setting['weekrow_class'] . '">' . $this->dayStrings['thu'][$this->setting['dayString']] . '</th>',
			'<th class="' . $this->setting['weekrow_class'] . '">' . $this->dayStrings['fri'][$this->setting['dayString']] . '</th>',
			'<th class="' . $this->setting['weekrow_class'] . ' ' . $this->setting['saturday_class'] . '">' . $this->dayStrings['sat'][$this->setting['dayString']] . '</th>',
			'</tr>'
		);
		
		return implode("\n", $row);
	}
}