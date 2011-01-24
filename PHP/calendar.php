<?php
/**
 * Usage:
 * $cal = new Calendar(2010, 6);
 * $cal->getCalendarAsXML('d');
 * 
 * $cal1 = new Calendar(2010, 6, Calendar::WEEK_START_MONDAY);
 * $cal1->getCalendar();
 * 
 */
class Calendar {
    
    const WEEK_START_SUNDAY = 0;
    const WEEK_START_MONDAY = 1;
    
    protected $_firstDay = null;
    protected $_lastDay  = null;
    
    public function __construct($year , $month, $weekStart = Calendar::WEEK_START_SUNDAY) {
        $countOfDays  = date('t', mktime(0, 0, 0, $month, 1, $year));        
        $this->_firstDay = mktime(0, 0, 0, $month, 1, $year);
        $this->_lastDay = mktime(0, 0, 0, $month, $countOfDays, $year);
        
        $firstWeekDay = date('w', $this->_firstDay);
        $lastWeekDay = date('w', $this->_lastDay);
        switch ( $weekStart ) {
            case 1:
                if ( $firstWeekDay == 0 ) $firstWeekDay = 7;
                if ( $firstWeekDay > 1 ) {
                    $this->_firstDay = mktime(0, 0, 0, $month, 2 - $firstWeekDay, $year);
                }
                if ( $lastWeekDay == 0 ) $lastWeekDay = 7;
                if ( $lastWeekDay < 7 ) {
                    $this->_lastDay = mktime(0, 0, 0, $month, $countOfDays + (7-$lastWeekDay), $year);
                }
                break;
            case 0:
            default:
                if ( $firstWeekDay > 0 ) {
                    $this->_firstDay = mktime(0, 0, 0, $month, 1 - $firstWeekDay, $year);
                }
                if ( $lastWeekDay < 6 ) {
                    $this->_lastDay = mktime(0, 0, 0, $month, $countOfDays + (6-$lastWeekDay), $year);
                }
                break;
        }
    }
    public function getFirstDay($format = 'Y-m-d') {
        return date('Y-m-d', $this->_firstDay);
    }
    public function getLastDay($format = 'Y-m-d') {
        return date('Y-m-d', $this->_lastDay);
    }
    public function getCalendar($format = 'Y-m-d') {
        $calendar = array();
        $i = 0;
        $j = 0;
        $day = $this->_firstDay;
        while ( $day <= $this->_lastDay ) {
            $calendar[$j][$i] = date($format, $day);
            $day = mktime(0, 0, 0, date('m', $day), date('d', $day)+1, date('Y', $day) );
            $i++;
            if ( $i%7 == 0 ) $j++;
        }
        return $calendar;
    }
    public function getCalendarAsXML( $format = 'Y-m-d', $events = array() ) {
        $calendar = $this->getCalendar($format);
        $xml = '<Calendar>';
        foreach ($calendar as $week) {
            $xml .= '<Week>';
            foreach ($week as $day) {
                if (is_array($events) && in_array($day, $events) ) {
                    $class = ' class="event" ';
                } else {
                    $class = '';
                }
                $xml .= '<Day'.$class.'>'.$day.'</Day>';
            }
            $xml .= '</Week>';
        }
        $xml .= '</Calendar>';
        return $xml;
    }
}
?>