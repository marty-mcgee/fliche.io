<?php

    /**
     * This file is part of the Fliche Video Toolkit v2 package.
     *
     * @author Oliver Lillie (aka buggedcom) <publicmail@buggedcom.co.uk>
     * @license Dual licensed under MIT and GPLv2
     * @copyright Copyright (c) 2008-2014 Oliver Lillie <http://www.buggedcom.co.uk>
     * @package FlicheToolkit V2
     * @version 2.1.7-beta
     * @uses ffmpeg http://ffmpeg.sourceforge.net/
     */
     
    namespace FlicheToolkit;
     
    /**
     * A class that is utilised by FlicheToolkit to manipulate and control timecode strings.
     *
     * @access public
     * @author Oliver Lillie
     * @package default
     */
    class Timecode
    {
        const INPUT_FORMAT_TIMECODE = -1;
        const INPUT_FORMAT_SECONDS = -2;
        const INPUT_FORMAT_MINUTES = -3;
        const INPUT_FORMAT_HOURS = -4;
        
        const EPSILON = 0.00001;
        
        protected $_total_frames;
        protected $_total_milliseconds;
        protected $_total_seconds;
        protected $_total_minutes;
        protected $_total_hours;
        
        protected $_frames;
        protected $_milliseconds;
        protected $_seconds;
        protected $_minutes;
        protected $_hours;

        protected $_is_negative;
        
        protected $_frame_rate;
        
        /**
         * Takes a time input format and converts it into seconds.
         * The object then allows you to extract different timecode formats from the input.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $input_value 
         * @param string $value_format 
         * @param string $timecode_format 
         * @param Media $media 
         */
        public function __construct($input_value, $value_format=Timecode::INPUT_FORMAT_SECONDS, $frame_rate=null, $timecode_format='%hh:%mm:%ss.%ms')
        {
            $this->_frames = null;
            $this->_milliseconds = 0;
            $this->_seconds = 0;
            $this->_minutes = 0;
            $this->_hours = 0;
            
            $this->_total_frames = null;
            $this->_total_milliseconds = 0;
            $this->_total_seconds = 0;
            $this->_total_minutes = 0;
            $this->_total_hours = 0;
            
            $this->setFrameRate($frame_rate);

//          convert the timecode to 
            $seconds = $this->_convertTimeInputToSeconds($input_value, $value_format, $timecode_format);
            
            $this->setSeconds($seconds);
        }

        /**
         * Sets the timecodes frame rate.
         *
         * @access public
         * @author: Oliver Lillie
         * @param  mixed $frame_rate Integer, Float or null.
         * @throws \InvalidArgumentException If the supplied from rate is not an integer, a float or a null value.
         */
        public function setFrameRate($frame_rate)
        {
            if(is_integer($frame_rate) === false && is_float($frame_rate) === false && is_null($frame_rate) === false)
            {
                throw new \InvalidArgumentException('The frame rate must be specified as either null, float or an integer value. `'.gettype($frame_rate).'` given.');
            }
            $this->_frame_rate = $frame_rate;
        }
        
        /**
         * When the object is converted to a string it is converted in a timecode string.
         *
         * @access public
         * @author Oliver Lillie
         * @return string
         */
        public function __toString()
        {
            return $this->full_timecode;
        }
        
        /**
         * Sets the timecodes seconds value but setting and parsing a timecode in the
         * given format.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $timecode_string A timecode string in the format given by $timecode_format.
         * @param string $timecode_format 
         * @return self
         */
        public function setTimecode($timecode_string, $timecode_format='%hh:%mm:%ss.%ms')
        {
            $seconds = $this->_convertTimeInputToSeconds($timecode_string, Timecode::INPUT_FORMAT_TIMECODE, $timecode_format);
            $this->setSeconds($seconds);
            return $this;
        }
        
        /**
         * Set the total seconds value of the timecode and recalculate the timecode values.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $seconds 
         * @return Timecode
         */
        public function setSeconds($seconds)
        {
            $seconds = (float) $seconds;

//          convert to totals
            $this->_total_milliseconds = $seconds*1000;
            $this->_total_seconds = $seconds;
            $this->_total_minutes = $seconds/60;
            $this->_total_hours = $this->_total_minutes/60;

            $this->_milliseconds =
            $this->_seconds =
            $this->_minutes =
            $this->_hours = 0;

//          convert to grouped
            $abs_seconds = abs($seconds);
            while($abs_seconds > 60)
            {
                $abs_seconds -= 60;
                $this->_minutes += 1;
                if($this->_minutes == 60)
                {
                    $this->_hours += 1;
                    $this->_minutes = 0;
                }
            }
            $this->_milliseconds = $abs_seconds-floor($abs_seconds);
            $this->_seconds = floor($abs_seconds);
            
//          if the milliseconds are 1, then make a seconds adjust
            if(abs($this->_milliseconds-1) < self::EPSILON)
            {
                $this->_milliseconds = 0;
                $this->_seconds += 1;
            }

            if($this->_seconds == 60)
            {
                $this->_minutes += 1;
                $this->_seconds = 0;
            }

//          determine if the value is negative.
            $this->_is_negative = $this->_total_seconds < 0;
            if($this->_is_negative === true)
            {
                $this->_milliseconds = -$this->_milliseconds;
                $this->_seconds = -$this->_seconds;
                $this->_minutes = -$this->_minutes;
                $this->_hours = -$this->_hours;
            }

//          if we have a frame rate then set those values.
            if($this->_frame_rate !== null)
            {
                $this->_frames = round($this->_frame_rate * $this->_milliseconds);
                $this->_total_frames = round($this->_frame_rate * $this->_total_seconds);
            }
            
            return $this;
        }

        /**
         * Resets the timecode to 00:00:00.000
         *
         * @access public
         * @author: Oliver Lillie
         * @return [type] [description]
         */
        public function reset()
        {
            $this->setSeconds(0);
        }
        
        /**
         * Set timecode values.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $property 
         * @param string $value 
         * @return void
         */
        public function __set($property, $value)
        {
            switch($property)
            {
                case 'hours' :
                
                    if($value > 0)
                    {
                        $this->_total_hours = $this->_total_hours - $this->_hours + $value;
                    }
                    else
                    {
                        $this->_total_hours = $this->_total_hours + ($value - $this->_hours);
                    }
                    $this->_total_seconds = $this->_total_hours * 60 * 60;
                    
                    break;
                    
                case 'mins' :
                case 'minutes' :
                
                    if($value > 0)
                    {
                        $this->_total_minutes = $this->_total_minutes - $this->_minutes + $value;
                    }
                    else
                    {
                        $this->_total_minutes = $this->_total_minutes + ($value - $this->_minutes);
                    }
                    $this->_total_seconds = $this->_total_minutes * 60;
                    
                    break;
                    
                case 'secs' :
                case 'seconds' :
                    if($value > 0)
                    {
                        $this->_total_seconds = $this->_total_seconds - $this->_seconds + $value;
                    }
                    else
                    {
                        $this->_total_seconds = $this->_total_seconds + ($value - $this->_seconds);
                    }
                    break;
                    
                case 'millisecs' :
                case 'milliseconds' :
                
                    if($value > 0)
                    {
                        $this->_total_milliseconds = $this->_total_milliseconds - $this->_milliseconds + $value;
                    }
                    else
                    {
                        $this->_total_milliseconds = $this->_total_milliseconds + ($value - $this->_milliseconds);
                    }
                    $this->_total_seconds = $this->_total_milliseconds/1000;

                    break;
                    
                case 'frame' :
                case 'frames' :
                
                    if($this->_frame_rate === null)
                    {
                        throw new Exception('You cannot set '.$property.' because the frame rate has not been set.');
                    }
                    if($value > 0)
                    {
                        $this->_total_frames = $this->_total_frames - $this->_frames + $value;
                    }
                    else
                    {
                        $this->_total_frames = $this->_total_frames + ($value - $this->_frames);
                    }
                    $this->_total_seconds = ($this->_total_frames / $this->_frame_rate);
                    
                    break;
                    
                default: 
                
                    throw new Exception('You cannot set '.$property.'.');
            }
            
           $this->setSeconds($this->_total_seconds);
        }
        
        /**
         * Allows access to protected non-setable values.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $property 
         * @return void
         */
        public function __get($property)
        {
            switch($property)
            {
                case 'timecode' :
                    return $this->getTimecode('%hh:%mm:%ss', true);
                    
                case 'full_timecode' :
                    return $this->getTimecode('%hh:%mm:%ss.%ms', true);
                    
                case 'total_hours' :
                    return $this->_total_hours;
                    
                case 'total_minutes' :
                    return $this->_total_minutes;
                    
                case 'total_seconds' :
                    return $this->_total_seconds;
                    
                case 'total_milliseconds' :
                    return $this->_total_milliseconds;
                    
                case 'hours' :
                    return $this->_hours;
                    
                case 'mins' :
                case 'minutes' :
                    return $this->_minutes;
                    
                case 'secs' :
                case 'seconds' :
                    return $this->_seconds;
                    
                case 'millisecs' :
                case 'milliseconds' :
                    return $this->_milliseconds;
               
                case 'frame' :
                case 'frames' :
                    return $this->_frames;
                
                case 'total_frames' :
                    return $this->_total_frames;
                
            }
        }
        
        /**
         * Outputs a timecode based on a specific format as supplied by $timecode_format.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $timecode_format The format of the timecode to return. The default is
         *      default '%ts'
         *          - %hh (hours) representative of hours
         *          - %mm (minutes) representative of minutes
         *          - %ss (seconds) representative of seconds
         *          - %fn (frame number) representative of frames (of the current second, not total frames)
         *          - %ms (milliseconds) representative of milliseconds (of the current second, not total milliseconds) (rounded to 3 decimal places)
         *          - %ft (frames total) representative of total frames (ie frame number)
         *          - %st (seconds total) representative of total seconds (rounded).
         *          - %sf (seconds floored) representative of total seconds (floored).
         *          - %sc (seconds ceiled) representative of total seconds (ceiled).
         *          - %mt (milliseconds total) representative of total milliseconds. (rounded to 3 decimal places)
         *      Thus you could use an alternative, '%hh:%mm:%ss:%ms', or '%hh:%mm:%ss' dependent on your usage.
         * @param string $use_smart_values Default value is TRUE, if a format is found (ie %ss - secs) but no higher format (ie %mm - mins)
         *      is found then if $use_smart_values is TRUE the value of of the format will be totaled.
         * @return void
         */
        public function getTimecode($timecode_format, $use_smart_values=true)
        {
            $searches = array();
            $replacements = array();
//          these ones are the simple replacements
//          replace the hours
            $using_hours = strpos($timecode_format, '%hh') !== false;
            if($using_hours === true)
            {
                array_push($searches, '%hh');
                array_push($replacements, str_pad(abs($this->_hours), 2, '0', STR_PAD_LEFT));
            }

//          replace the minutes
            $using_mins = strpos($timecode_format, '%mm') !== false;
            if($using_mins === true)
            {
                array_push($searches, '%mm');
//              check if hours are being used, if not and hours are required enable smart minutes
                if($use_smart_values === true && $using_hours === false && $this->_hours > 0)
                {
                    $value = ($this->_hours * 60) + $this->_minutes;
                }
                else
                {
                    $value = $this->_minutes;
                }
                array_push($replacements, str_pad(abs($value), 2, '0', STR_PAD_LEFT));
            }

//          replace the seconds
            if(strpos($timecode_format, '%ss') !== false)
            {
//              check if hours are being used, if not and hours are required enable smart minutes
                if($use_smart_values === true && $using_mins === false && $using_hours === false && $this->_hours > 0)
                {
                    $mins = ($this->_hours * 60) + $this->_minutes;
                }
//              check if mins are being used, if not and hours are required enable smart minutes
                if($use_smart_values === true && $using_mins === false && $this->_minutes > 0)
                {
                    $value = ($mins * 60) + $this->_seconds;
                }
                else
                {
                    $value = $this->_seconds;
                }

                // if($value === 60)
                // {
                //     $this->_minutes += 1;
                //     $this->_seconds = 0;
                // }

                array_push($searches, '%ss');
                array_push($replacements, str_pad(abs($value), 2, '0', STR_PAD_LEFT));
            }
//          replace the milliseconds
            if(strpos($timecode_format, '%ms') !== false)
            {
                array_push($searches, '%ms');
                array_push($replacements, str_pad(round(abs($this->_milliseconds), 3)*1000, 3, '0', STR_PAD_LEFT));
            }
//          replace the total seconds (rounded)
            if(strpos($timecode_format, '%st') !== false)
            {
                array_push($searches, '%st');
                array_push($replacements, str_pad(round(abs($this->_seconds)), 2, '0', STR_PAD_LEFT));
            }
//          replace the total seconds (floored)
            if(strpos($timecode_format, '%sf') !== false)
            {
                array_push($searches, '%sf');
                array_push($replacements, str_pad(floor(abs($this->_seconds+$this->_milliseconds)), 2, '0', STR_PAD_LEFT));
            }
//          replace the total seconds (ceiled)
            if(strpos($timecode_format, '%sc') !== false)
            {
                array_push($searches, '%sc');
                array_push($replacements, str_pad(ceil(abs($this->_seconds+$this->_milliseconds)), 2, '0', STR_PAD_LEFT));
            }
//          replace the total seconds
            if(strpos($timecode_format, '%mt') !== false)
            {
                array_push($searches, '%mt');
                array_push($replacements, round(abs($this->_seconds+$this->_milliseconds), 3));
            }
//          these are the more complicated as they depend on $frames_per_second / frames per second of the current input
            $has_frames = strpos($timecode_format, '%fn') !== false;
            $has_total_frames = strpos($timecode_format, '%ft') !== false;
            if($has_frames === true || $has_total_frames === true)
            {
//              if the fps is false then we must automagically detect it from the input file
                if($this->_frame_rate === null)
                {
                    // TODO throw exception
                    return -1;
                }
//              check the information has been received
                $frames_per_second = $this->_frame_rate;
                if(!$frames_per_second)
                {
//                  fps cannot be reached so return -1
                    return -1;
                }
                    
//              replace the frames
                $excess_frames = false;
                if($has_frames === true)
                {
                    $excess_frames = ceil(($this->_seconds - floor($this->_seconds)) * $frames_per_second);
                    array_push($searches, '%fn');
                    array_push($replacements, abs($excess_frames));
                }
//              replace the total frames (ie frame number)
                if($has_total_frames === true)
                {
                    $round_frames = floor($this->_seconds) * $frames_per_second;
                    if($excess_frames === false)
                    {
                        $excess_frames = ceil(($this->_seconds - floor($this->_seconds)) * $frames_per_second);
                    }
                    array_push($searches, '%ft');
                    array_push($replacements, abs($round_frames + $excess_frames));
                }
            }
            return ($this->_is_negative === true ? '-' : '').str_replace($searches, $replacements, $timecode_format);
        }
        
        /**
         * Converts a timecode to seconds.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $input_value 
         * @param string $value_format 
         * @param string $timecode_format 
         * @return void
         */
        protected function _convertTimeInputToSeconds($input_value, $value_format, $timecode_format=null)
        {
            if(in_array($value_format, array(self::INPUT_FORMAT_TIMECODE, self::INPUT_FORMAT_SECONDS, self::INPUT_FORMAT_MINUTES, self::INPUT_FORMAT_HOURS)) === false)
            {
                throw new \InvalidArgumentException('Invalid timecode value format supplied to Timecode::__construct.');
            }
            
            switch($value_format)
            {
                case self::INPUT_FORMAT_TIMECODE :
                    return self::parseTimecode($input_value, $timecode_format, $this->_frame_rate, null);
                    break;
                    
                case self::INPUT_FORMAT_SECONDS :
                    return $input_value;
                    break;
                    
                case self::INPUT_FORMAT_MINUTES :
                    return $input_value*60;
                    break;
                    
                case self::INPUT_FORMAT_HOURS :
                    return $input_value*60*60;
                    break;
            }
        }
        
        /**
         * Parses a timecode according to the given input format. The value of the timecode in seconds is returned.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $input_value The timecode input value that you are parsing.
         * @param string $input_format 
         *          - %hh (hours) representative of hours
         *          - %mm (minutes) representative of minutes
         *          - %ss (seconds) representative of seconds
         *          - %fn (frame number) representative of frames (of the current second, not total frames)
         *          - %ms (milliseconds) representative of milliseconds (of the current second, not total milliseconds) (rounded to 3 decimal places)
         *          - %ft (frames total) representative of total frames (ie frame number)
         *          - %st (seconds total) representative of total seconds (rounded).
         *          - %sf (seconds floored) representative of total seconds (floored).
         *          - %sc (seconds ceiled) representative of total seconds (ceiled).
         *          - %mt (milliseconds total) representative of total milliseconds. (rounded to 3 decimal places)
         * @param integer $frames_per_second The number of frames per second to translate for. If left as null
         *      the class automagically gets the fps from Media->frames_per_second, but the Media param has to be set
         *      first for this to work properly.
         * @param Media $media If you are using frames and wish to inherit the frame rate directly from the media object
         *      then you can set the media object using this parameter.
         * @return integer Returns a positive value in seconds if successfull, alternatively returns -1 and a frame rate error.
         */
        public static function parseTimecode($input_value, $input_format, $frames_per_second=null, Media $media=null)
        {
//          first we must get the timecode into the current seconds
            $input_quoted = preg_quote($input_format);
            $placeholders = array('%hh', '%mm', '%ss', '%fn', '%ms', '%ft', '%st', '%sf', '%sc', '%mt');
            $input_regex = str_replace($placeholders, '([0-9]+)', preg_quote($input_format));
            preg_match('/'.$input_regex.'/', $input_value, $matches);
            
//          work out the sort order for the placeholders
            $sort_table = array();
            foreach($placeholders as $key => $placeholder)
            {
                if(($pos = strpos($input_format, $placeholder)) !== false)
                {
                    $sort_table[$pos] = $placeholder;
                }
            }
            ksort($sort_table);
            
//          check to see if frame related values are in the input
            $has_frames = strpos($input_format, '%fn') !== false;
            $has_total_frames = strpos($input_format, '%ft') !== false;
            if ($has_frames === true || $has_total_frames === true)
            {
//              if the fps is false then we must automagically detect it from the input file
                if($frames_per_second === null)
                {
//                  check the information has been received
                    if($media === null)
                    {
                        // TODO throw exception
                        return -1;
                    }
                    $frames_per_second = $media->frames_per_second;
                    if(!$frames_per_second)
                    {
                        return -1;
                    }
                }
            }
            
//          increment the seconds with each placeholder value
            $seconds = 0;
            $key = 1;
            foreach ($sort_table as $placeholder)
            {
                if(isset($matches[$key]) === false)
                {
                    break;
                }
                
                $value = $matches[$key];
                switch ($placeholder)
                {
//                  time related ones
                    case '%hh' :
                        $seconds += $value * 3600;
                        break;
                    case '%mm' :
                        $seconds += $value * 60;
                        break;
                    case '%ss' :
                    case '%sf' :
                    case '%sc' :
                        $seconds += $value;
                        break;
                    case '%ms' :
                        $seconds += floatval('0.' . $value);
                        break;
                    case '%st' :
                    case '%mt' :
                        $seconds = $value;
                        break 1;
                        break;
//                  frame related ones
                    case '%fn' :
                        $seconds += $value / $frames_per_second;
                        break;
                    case '%ft' :
                        $seconds = $value / $frames_per_second;
                        break 1;
                        break;
                }
                $key += 1;
            }

            return $seconds;
        }
    }
