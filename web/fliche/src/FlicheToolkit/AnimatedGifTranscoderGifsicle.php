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
     * This class provides an animated gif transcoder engine that uses gifsicle and either imagemagic convert or PHP gd to create 
     * animated gifs. This engine is the prefered engine in conjunction with convert. GD still does ok, but takes much much longer.
     *
     * @author Oliver Lillie
     */
    class AnimatedGifTranscoderGifsicle extends AnimatedGifTranscoderAbstract
    {
        /**
         * A list of temporary gif files to clean up.
         * @var array
         */
        protected $_tidy_up_gifs;
        
        /**
         * Constructor
         *
         * @access public
         * @author Oliver Lillie
         * @param  FlicheToolkit\Config $config The PHPVideoToolit\Config object.
         */
        public function __construct(Config $config=null)
        {
            parent::__construct($config);
            
            $this->_tidy_up_gifs = array();
        }
        
        /**
         * Destructor. Automatically removes temporary gif files.
         *
         * @access public
         * @author Oliver Lillie
         */
        public function __destruct()
        {
//          tidy up any converted image to gifs.
            if(empty($this->_tidy_up_gifs) === false)
            {
                foreach ($this->_tidy_up_gifs as $path)
                {
                    @unlink($path);
                }
                $this->_tidy_up_gifs = array();
            }
        }
        
        /**
         * Adds a frame to the gif. This overrides the parent abstract class addFrame function.
         *
         * @access public
         * @author Oliver Lillie
         * @param  FlicheToolkit\Image $image The image source that you wish to add as a frame to the gif.
         * @return FlicheToolkit\AnimatedGifTranscoderGifsicle Returns the current object.
         */
        public function addFrame(Image $image)
        {
//          gifsicle doesn't work with non-gif images so we must convert a frame to a gif
//          if it is not a gif.
            $gif_path = $this->_convertFrameToGif($image->getMediaPath());
            
            array_push($this->_frames, $gif_path);
            
            return $this;
        }
        
        /**
         * Converts a given image if the image is not a gif, to a gif, using either imagemagic convert or PHP GD.
         * Convert is preferred as it is quicker.
         *
         * @access public
         * @author Oliver Lillie
         * @param  string $frame_path The file paht of the image to convert.
         * @return string Returns the new file path if the image was converted, otherwise returns the old file path.
         * @throws FlicheToolkit\AnimatedGifException If imagemagick convert is user but encounters an error.
         * @throws \RuntimeException If PHP GD is used and imagegif is not available on the current system.
         * @throws FlicheToolkit\AnimatedGifException If the image given to convert is not supported by PHP GD.
         * @throws FlicheToolkit\AnimatedGifException If the image fails to convert to a gif using PHP GD.
         * @throws FlicheToolkit\AnimatedGifException If the image fails to convert.
         * @throws FlicheToolkit\AnimatedGifException If the image fails to convert but a path was generated.
         * @throws FlicheToolkit\AnimatedGifException If the image fails to convert but a file was generated by 0 bytes in size.
         */
        protected function _convertFrameToGif($frame_path)
        {
            $data = getimagesize($frame_path);
            if($data['mime'] === 'image/gif')
            {
                return $frame_path;
            }

            $gif_path = null;
            
//          first try with imagemagick convert as imagemaigk produces better gifs
            if($this->_config->convert)
            {
                $process = new ProcessBuilder('convert', $this->_config);
                $process->add($frame_path)
                        ->add($frame_path.'.convert-convert.gif');

                if($this->_config->gif_transcoder_convert_use_dither === true)
                {
                    $process->add('-ordered-dither')->add($this->_config->gif_transcoder_convert_dither_order);
                }
                if($this->_config->gif_transcoder_convert_use_map === true)
                {
                    $process->add('+map');
                }

                $exec = $process->getExecBuffer();
                $exec->setBlocking(true)
                     ->execute();
                if($exec->hasError() === true)
                {
                    throw new AnimatedGifException('When attempting to convert "'.$frame_path.'" to a gif, convert encountered an error. Convert reported: '.$exec->getLastLine());
                }
                
                $gif_path = $frame_path.'.convert-convert.gif';
            }
            
//          if we still have no gif path then try with php gd.
            if(empty($gif_path) === true)
            {
                if(function_exists('imagegif') === false)
                {
                    throw new \RuntimeException('PHP GD\'s function `imagegif` is not available, as a result the frame could not be added.');
                }
                
                $im = false;
                $data = getimagesize($frame_path);
                switch($data['mime'])
                {
                    case 'image/jpeg' :
                        $im = @imagecreatefromjpeg($frame_path);
                        break;
                    case 'image/png' :
                        $im = @imagecreatefrompng($frame_path);
                        break;
                    case 'image/xbm' :
                        $im = @imagecreatefromwbmp($frame_path);
                        break;
                    case 'image/xpm' :
                        $im = @imagecreatefromxpm($frame_path);
                        break;
                }
                if($im === false)
                {
                    throw new AnimatedGifException('Unsupported image type.');
                }
                
//              save as a gif
                $gif_path = $frame_path.'.convert-php.gif';
                if(imagegif($im, $gif_path) === false)
                {
                    throw new AnimatedGifException('Unable to convert frame to gif using PHP GD.');
                }
                imagedestroy($im);
            }
            
            if(empty($gif_path) === true)
            {
                throw new AnimatedGifException('Unable to convert frame to gif.');
            }
            else if(is_file($gif_path) === false)
            {
                throw new AnimatedGifException('Unable to convert frame to gif, however the gif conversion path was set.');
            }
            else if(filesize($gif_path) === 0)
            {
                throw new AnimatedGifException('Unable to convert frame to gif, as a gif was produced, however it was a zero byte file.');
            }
            
            array_push($this->_tidy_up_gifs, $gif_path);
            
            return $gif_path;
        }
        
        /**
         * Saves the animated gif.
         *
         * @access public
         * @author Oliver Lillie
         * @param  string $save_path The path to save the animated gif to.
         * @return FlicheToolkit\Image Returns a new instance of FlicheToolkit\Image with the new animated gif as the src.
         * @throws FlicheToolkit\AnimatedGifException If the convert process encounters an error.
         */
        public function save($save_path)
        {
            $save_path = parent::save($save_path);
            
//          build the gifsicle process
            $gifsicle_process = new ProcessBuilder('gifsicle', $this->_config);
            
//          add in all the frames
            foreach ($this->_frames as $path)
            {
                $gifsicle_process->add($path);
            }
            
//          set the looping count
            if($this->_loop_count === AnimatedGif::UNLIMITED_LOOPS)
            {
                $gifsicle_process->add('-l');
            }
            else
            {
                $gifsicle_process->add('-l')->add($this->_loop_count);
            }
            
//          set the frame duration
            //$gifsicle_process->add('-d')->add($frame_delay*1000);

//          add the output path
            $gifsicle_process->add('-o')->add($save_path);
            
//          execute the process.
            $exec = $gifsicle_process->getExecBuffer();
            $exec->setBlocking(true)
                 ->execute();
            
            $this->__destruct();

//          check for any gifsicle errors
            if($exec->hasError() === true)
            {
                throw new AnimatedGifException('AnimatedGif save using `gifsicle` save "'.$save_path.'" failed. Any additional gifsicle message follows: 
'.$exec->getBuffer());
            }
            
            return new Image($save_path, $this->_config);
        }
        
        /**
         * Determines if the gifsicle/convert or gifsicle/php transcoder engine is available on the current system.
         *
         * @access public
         * @static
         * @author Oliver Lillie
         * @param  FlicheToolkit\Config $config The configuration object.
         * @return boolean Returns true if this engine can be used, otherwise false.
         */
        public static function available(Config $config)
        {
            if($config->gifsicle === null)
            {
                return false;
            }
            
            try
            {
                Binary::locate($config->gifsicle);
                try
                {
                    Binary::locate($config->convert);
                    return true;
                }
                catch(Exception $e)
                {
                    return function_exists('imagegif');
                }
            }
            catch(Exception $e)
            {
                return false;
            }
        }
        
    }
