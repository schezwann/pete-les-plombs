<?php
    include_once($PATH . '/class/mysql.class.inc.php');

	class Event
	{
        private int $_id;

        //media
        private string $_url;
        private string $_caption;
        private string $_credit;
        private string $_thumbnail;

        //text
        private string $_headline;
        private string $_text;

        //start_date
        private string $_startDay;
        private string $_startMonth;
        private string $_startYear;

        //end_date
        private string $_endDay;
        private string $_endMonth;
        private string $_endYear;

        private bool $_loaded = false;

        public function getId() : int { return $this->_id; }

        public function getMediaDatas():array { return ['url'=>$this->getUrl(), 'caption'=>$this->getCaption(), 'credit'=>$this->getCredit()]; }
        public function getUrl():string { return $this->_url; }
        public function getCaption():string { return $this->_caption; }
        public function getCredit():string { return $this->_credit; }
        public function getThumbnail():string { return $this->_thumbnail; }

        public function getTextDatas():array { return ['headline'=>$this->getHeadline(), 'text'=>$this->getText()]; }
        public function getHeadline():string { return $this->_headline; }
        public function getText():string { return $this->_text; }

        public function getStartDateDatas():array { return ['day'=>$this->getStartDay(), 'month'=>$this->getStartMonth(), 'year'=>$this->getStartYear()]; }
        public function getStartDay():int { return $this->_startDay; }
        public function getStartMonth():int { return $this->_startMonth; }
        public function getStartYear():int { return $this->_startYear; }
        
        public function getEndDateDatas():array { return ['day'=>$this->getEndDay(), 'month'=>$this->getEndMonth(), 'year'=>$this->getEndYear()]; }
        public function getEndDay():int { return $this->_endDay; }
        public function getEndMonth():int { return $this->_endMonth; }
        public function getEndYear():int { return $this->_endYear; }


        public function __construct(int $id)
        {
            global $PATH;
            include($PATH . '/class/database.inc.php');

            $eventId = $db->QuoteSmartNumeric($panier);
            $sql = "SELECT * FROM events WHERE id=$eventId";

            if($db->Query($sql, __CLASS__.'::'.__FUNCTION__ ) && $row=$db->FetchObject())
            {
                if(!empty($row->id))
                    $this->_id = $row->id;

                //media
                if(!empty($row->url))
                    $this->_url = $row->url;
                if(!empty($row->caption))
                    $this->_caption = $row->caption;
                if(!empty($row->credit))
                    $this->_credit = $row->credit;
                if(!empty($row->thumbnail))
                    $this->_thumbnail = $row->thumbnail;

                //text
                if(!empty($row->headline))
                    $this->_headline = $row->headline;
                if(!empty($row->text))
                    $this->_text = $row->text;

                //start_date
                if(!empty($row->startDay))
                    $this->_startDay = $row->startDay;
                if(!empty($row->startMonth))
                    $this->_startMonth = $row->startMonth;
                if(!empty($row->startYear))
                    $this->_startYear = $row->startYear;

                //end_date
                if(!empty($row->endDay))
                    $this->_endDay = $row->endDay;
                if(!empty($row->endMonth))
                    $this->_endMonth = $row->endMonth;
                if(!empty($row->endYear))
                    $this->_endYear = $row->endYear;

                $this->_loaded = true;
            }
        }
    }
?>