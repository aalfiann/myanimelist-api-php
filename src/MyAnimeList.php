<?php
    namespace aalfiann;
    //libxml_use_internal_errors(true);
    /**
     * A class for access MyAnimeList
     *
     * @package    MyAnimeList API PHP Class
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/myanimelist-api-php/blob/master/LICENSE.md  MIT License
     */
    class MyAnimeList {

        var $login,$proxy,$proxyauth,$pretty = false;

        private $animesearch = 'https://myanimelist.net/api/anime/search.xml?q=';
        private $mangasearch = 'https://myanimelist.net/api/manga/search.xml?q=';
        private $verifycredentials = 'https://myanimelist.net/api/account/verify_credentials.xml';

        private $animegrab = 'https://myanimelist.net/anime/';
        private $mangagrab = 'https://myanimelist.net/manga/';

        private $animegrabsearch = 'https://myanimelist.net/anime.php?q=';
        private $mangagrabsearch = 'https://myanimelist.net/manga.php?q=';

        private $animeHTML,$mangaHTML,$HTMLTable,$HTMLSearch,$searchlink; 

        /**
		 * CURL Get Request
         *
         * @param $url = The url api to get the request
		 * @return result response data
		 */
        private function execGetRequest($url){
            //open connection
	    	$ch = curl_init($url);
            
            //curl parameter
            if (!empty($this->login)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $this->login);
            }
            if (!empty($this->proxy)) curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            if (!empty($this->proxyauth)) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
	    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
            
            //execute post
		    $response = curl_exec($ch);
            
            //close connection
    		curl_close($ch);

	    	return $response;
        }

        /**
         * Convert xml data to json
         * 
         * @param $data = The data response from curl
         * @return json 
         */
        private function convertXMLToJSON($data){
            $xml = simplexml_load_string($data);
            if ($xml === false){
                return json_encode(['status' => 'error','message' => $data]);
            } else {
                return json_encode($xml);
            }
        }

        /**
         * Beautifier json response
         * Support for PHP 5.3
         * 
         * @param $json = the json data
         * @return json
         */
        private function prettyPrint($json){
            $result = '';
            $level = 0;
            $in_quotes = false;
            $in_escape = false;
            $ends_line_level = NULL;
            $json_length = strlen( $json );
        
            for( $i = 0; $i < $json_length; $i++ ) {
                $char = $json[$i];
                $new_line_level = NULL;
                $post = "";
                if( $ends_line_level !== NULL ) {
                    $new_line_level = $ends_line_level;
                    $ends_line_level = NULL;
                }
                if ( $in_escape ) {
                    $in_escape = false;
                } else if( $char === '"' ) {
                    $in_quotes = !$in_quotes;
                } else if( ! $in_quotes ) {
                    switch( $char ) {
                        case '}': case ']':
                            $level--;
                            $ends_line_level = NULL;
                            $new_line_level = $level;
                            break;
        
                        case '{': case '[':
                            $level++;
                        case ',':
                            $ends_line_level = $level;
                            break;
        
                        case ':':
                            $post = " ";
                            break;
        
                        case " ": case "\t": case "\n": case "\r":
                            $char = "";
                            $ends_line_level = $new_line_level;
                            $new_line_level = NULL;
                            break;
                    }
                } else if ( $char === '\\' ) {
                    $in_escape = true;
                }
                if( $new_line_level !== NULL ) {
                    $result .= "\n".str_repeat( "\t", $new_line_level );
                }
                $result .= $char.$post;
            }
        
            return $result;
        }

        /**
         * checkProxy connection
         */
        public function checkProxy(){
            header ("Content-Type:application/json");
            $url = 'http://dynupdate.no-ip.com/ip.php';
            $this->login = "";
            $data = $this->execGetRequest($url);
            if (!empty($data)){
                $result = json_encode([
                    'status' => 'success',
                    'message' => 'Your IP address: '.$data,
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ]);
            } else {
                $result = json_encode([
                    'status' => 'error',
                    'message' => 'Failed to get any response from '.$url.'.',
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ]);
            }
            if ($this->pretty){
                return $this->prettyPrint($result);
            } else {
                return $result;
            }
        }

        /**
         * Search Anime
         *
         * @param $title = the title to search
         * @param $listing = the data will show as listing or single. Default value is false.
         * @return json
         */
        public function searchAnime($title,$listing = false){
            header ("Content-Type:application/json");
            $data = $this->execGetRequest($this->animesearch.rawurlencode($title));
            if (!empty($data)){
                $datares = json_decode($this->convertXMLToJSON($data));
                if (!empty($datares)){
                    if (is_array($datares->{'entry'})){
                        if ($listing){
                            $result = json_encode([
                                'entry' => $datares->{'entry'},
                                'status' => 'success',
                                'logger' => [
                                    'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                                    'timestamp' => date('Y-m-d h:i:s', time())
                                ]
                            ]);
                        } else {
                            $this->grabHTMLAnime($datares->entry[0]->{'id'});
                            $result = json_encode([
                                'entry' => $datares->{'entry'}[0],
                                'metadata' => $this->getMetadataAnime($this->animeHTML),
                                'status' => 'success',
                                'logger' => [
                                    'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                                    'timestamp' => date('Y-m-d h:i:s', time())
                                ]
                            ]);
                        }
                    } else {
                        $this->grabHTMLAnime($datares->entry->{'id'});
                        $result = json_encode([
                            'entry' => $datares->{'entry'},
                            'metadata' => $this->getMetadataAnime($this->animeHTML),
                            'status' => 'success',
                            'logger' => [
                                'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                                'timestamp' => date('Y-m-d h:i:s', time())
                            ]
                        ]);
                    }
                } else {
                    $result = json_encode([
                        'status' => 'error',
                        'message' => 'Failed to get any response from MyAnimeList.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                }
            } else {
                $result = json_encode([
                    'status' => 'error',
                    'message' => 'Failed to get any response from MyAnimeList.',
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ]);
            }
            if ($this->pretty){
                return $this->prettyPrint($result);
            } else {
                return $result;
            }
        }

        /**
         * Search Manga
         * 
         * @param $title = the title to search
         * @param $listing = the data will show as listing or single. Default value is false.
         * @return json
         */
        public function searchManga($title,$listing=false){
            header ("Content-Type:application/json");
            $data = $this->execGetRequest($this->mangasearch.rawurlencode($title));
            if (!empty($data)){
                $datares = json_decode($this->convertXMLToJSON($data));
                if (!empty($datares)){
                    if (is_array($datares->{'entry'})){
                        if ($listing){
                            $result = json_encode([
                                'entry' => $datares->{'entry'},
                                'status' => 'success',
                                'logger' => [
                                    'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                                    'timestamp' => date('Y-m-d h:i:s', time())
                                ]
                            ]);
                        } else {
                            $this->grabHTMLManga($datares->entry[0]->{'id'});
                            $result = json_encode([
                                'entry' => $datares->{'entry'}[0],
                                'metadata' => $this->getMetadataManga($this->mangaHTML),
                                'status' => 'success',
                                'logger' => [
                                    'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                                    'timestamp' => date('Y-m-d h:i:s', time())
                                ]
                            ]);
                        }
                    } else {
                        $this->grabHTMLManga($datares->entry->{'id'});
                        $result = json_encode([
                            'entry' => $datares->{'entry'},
                            'metadata' => $this->getMetadataManga($this->mangaHTML),
                            'status' => 'success',
                            'logger' => [
                                'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                                'timestamp' => date('Y-m-d h:i:s', time())
                            ]
                        ]);
                    }
                } else {
                    $result = json_encode([
                        'status' => 'error',
                        'message' => 'Failed to get any response from MyAnimeList.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                }
            } else {
                $result = json_encode([
                    'status' => 'error',
                    'message' => 'Failed to get any response from MyAnimeList.',
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ]);
            }
            if ($this->pretty){
                return $this->prettyPrint($result);
            } else {
                return $result;
            }
        }

        /**
         * Verify Credentials
         * @return json
         */
        public function verify(){
            header ("Content-Type:application/json");
            $data = $this->execGetRequest($this->verifycredentials);
            if (!empty($data)){
                $datares = json_decode($this->convertXMLToJSON($data));
                if (!empty($datares)){
                    $result = json_encode([
                        'user' => $datares,
                        'status' => 'success',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                } else {
                    $result = json_encode([
                        'status' => 'error',
                        'message' => 'Failed to get any response from MyAnimeList.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                }
            } else {
                $result = json_encode([
                    'status' => 'error',
                    'message' => 'Failed to get any response from MyAnimeList.',
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ]);
            }
            if ($this->pretty){
                return $this->prettyPrint($result);
            } else {
                return $result;
            }
        }

        /**
         * Below here is Un Official way using grabbing, will broken in the future if MyAnimeList update or change the template.
         * So use with your own risk.
         * 
         * This script was first created at December 2017.
         * Hasbeen updated at August 2018.
         */

        /* Grabbing Anime */
        //Tested using anime >> https://myanimelist.net/anime/2889 or https://myanimelist.net/anime/33502

        private function grabHTMLAnime($id){
            $this->animeHTML = $this->execGetRequest($this->animegrab.$id);
        }

        private function getSpanDom($class,$value,$html){
            if (!empty($html)){
                $tmp = explode('<span class="'.$class.'">'.$value.'</span>',$html);
                if (!empty($tmp[1])){
                    $tmp2 = explode('</div>',$tmp[1]);
                    return trim($tmp2[0]);
                } else {
                    return "";
                }
            } else {
                return "";
            } 
        }

        private function getSpanCustom($attr,$html){
            if (!empty($html)){
                $tmp = explode('<span '.$attr.'>',$html);
                if (!empty($tmp[1])){
                    $tmp2 = explode('</span>',$tmp[1]);
                    return trim($tmp2[0]);
                } else {
                    return "";
                }
            } else {
                return "";
            } 
        }

        private function getSpanLink($class,$value,$html){
            if (!empty($html)){
                $tmp = explode('<span class="'.$class.'">'.$value.'</span>',$html);
                if (!empty($tmp[1])){
                    $tmp2 = explode('</div>',$tmp[1]);
                    $tmp3 = explode(', ',$tmp2[0]);
                    $tags = null;
                    foreach ($tmp3 as $key){
                        $data = explode('">', $key);
                        if (!empty($data[1])){
                            $data2 = explode("</a>",$data[1]);
                            $tags .= $data2[0].', ';
                        } else {
                            $tags .= '';
                        }
                    }
                    $result = substr($tags, 0, -2);
                    return str_replace('add some','',$result);
                } else {
                    return "";
                }
            } else {
                return "";
            }
        }

        private function getPropertyDom($property,$html){
            if (!empty($html)){
                $tmp = explode('<meta property="'.$property.'" content="',$html);
                if (!empty($tmp[1])){
                    $tmp2 = explode('">',$tmp[1]);
                    return trim($tmp2[0]);
                } else {
                    return "";
                }
            } else {
                return "";
            } 
        }

        private function getID($id){
            return $id;
        }

        private function getTitle($html){
            return $this->getPropertyDom('og:title',$html);
        }

        private function getEnglish($html){
            return $this->getSpanDom('dark_text','English:',$html);
        }

        private function getSynonyms($html){
            return $this->getSpanDom('dark_text','Synonyms:',$html);
        }

        private function getJapanese($html){
            return $this->getSpanDom('dark_text','Japanese:',$html);
        }

        private function getScore($html){
            return $this->getSpanCustom('itemprop="ratingValue"',$html);
        }

        private function getType($html){
            $data = explode('">',$this->getSpanDom('dark_text','Type:',$html));
            if (!empty($data[1])){
                return str_replace('</a>','',$data[1]);
            } else {
                return "";
            }
        }

        private function getStatus($html){
            return $this->getSpanDom('dark_text','Status:',$html);
        }

        private function getSynopsis($html){
            return $this->getPropertyDom('og:description',$html);
        }

        private function getImage($html){
            return $this->getPropertyDom('og:image',$html);
        }

        private function getGenre($html){
            return $this->getSpanLink('dark_text','Genres:',$html);
        }

        private function getAnimeEpisode($html){
            return $this->getSpanDom('dark_text','Episodes:',$html);
        }

        private function getAnimeStartDate($html){
            $dt = explode(' to ',$this->getSpanDom('dark_text','Aired:',$html));
            if (!empty($dt[0])){
                return (($dt[0] != '?')?date('Y-m-d',strtotime($dt[0])):'0000-00-00'); 
            } else {
                return '0000-00-00';
            }
        }

        private function getAnimeEndDate($html){
            $dt = explode(' to ',$this->getSpanDom('dark_text','Aired:',$html));
            if (!empty($dt[1])){
                return (($dt[1] != '?')?date('Y-m-d',strtotime($dt[1])):'0000-00-00'); 
            } else {
                return '0000-00-00';
            }
        }

        private function getAnimeProducer($html){
            return $this->getSpanLink('dark_text','Producers:',$html);
        }

        private function getAnimeLicensor($html){
            return $this->getSpanLink('dark_text','Licensors:',$html);
        }

        private function getAnimeStudio($html){
            return $this->getSpanLink('dark_text','Studios:',$html);
        }

        private function getAnimeSource($html){
            return str_replace(', add some','',$this->getSpanDom('dark_text','Source:',$html));
        }

        private function getMetadataAnime($html){
            return [
                'genres' => $this->getGenre($html),
                'producers' => $this->getAnimeProducer($html),
                'licensors' => $this->getAnimeLicensor($html),
                'studios' => $this->getAnimeStudio($html),
                'source' => $this->getAnimeSource($html)
            ];
        }

        /**
         * Grab Anime
         *  
         * @param $id = anime id
         * @return json
         */
        public function grabAnime($id){
            header ("Content-Type:application/json");
            $this->grabHTMLAnime($id);
            $html = $this->animeHTML;
            if (!empty($html)){
                $statusdata = ((strpos($this->getTitle($html),'404 Not Found') !== false)?'error':'success');
                if ($statusdata == 'success'){
                    $result = json_encode([
                        'entry' => [
                            'id' => $this->getID($id),
                            'title' => $this->getTitle($html),
                            'english' => $this->getEnglish($html),
                            'japanese' => $this->getJapanese($html),
                            'synonyms' => $this->getSynonyms($html),
                            'episodes' => $this->getAnimeEpisode($html),
                            'score' => $this->getScore($html),
                            'type' => $this->getType($html),
                            'status' => $this->getStatus($html),
                            'start_date' => $this->getAnimeStartDate($html),
                            'end_date' => $this->getAnimeEndDate($html),
                            'synopsis' => $this->getSynopsis($html),
                            'image' => $this->getImage($html)
                        ],
                        'metadata' => $this->getMetadataAnime($html),
                        'status' => $statusdata,
                        'message' => 'Data found.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                } else {
                    $result = json_encode([
                        'status' => $statusdata,
                        'message' => 'Data not found.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                }     
            } else {
                $result = json_encode([
                    'status' => 'error',
                    'message' => 'Failed to get any response from MyAnimeList.',
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ]);
            }
            if ($this->pretty){
                return $this->prettyPrint($result);
            } else {
                return $result;
            }
        }

        /* Grabbing Manga */
        //Tested using manga Overlord >> https://myanimelist.net/manga/81667

        private function grabHTMLManga($id){
            $this->mangaHTML = $this->execGetRequest($this->mangagrab.$id);
        }

        private function getMangaChapter($html){
            return $this->getSpanDom('dark_text','Chapters:',$html);
        }

        private function getMangaVolume($html){
            return $this->getSpanDom('dark_text','Volumes:',$html);
        }

        private function getMangaAuthor($html){
            if (!empty($html)){
                $tmp = explode('<span class="dark_text">Authors:</span>',$html);
                if (!empty($tmp[1])){
                    $tmp2 = explode('</div>',$tmp[1]);
                    $tmp3 = explode(', <',$tmp2[0]);
                    $tags = null;
                    foreach ($tmp3 as $key){
                        $data = explode('">', $key);
                        $tags .= str_replace('</a>','',$data[1]).' | ';
                    }
                    $result = substr($tags, 0, -3);
                    return $result;
                } else {
                    return "";
                }
            } else {
                return "";
            }
        }

        private function getMangaAuthorFormatted($str){
            if (!empty($str)){
                $data = explode(' | ',$str);
                $build = null;
                foreach ($data as $key){
                    $dataa = explode(' (',$key);
                    $datab = explode(', ',$dataa[0]);
                    $build .= (!empty($datab[1])?$datab[1]:'').' '.(!empty($datab[0])?$datab[0]:'').(!empty($dataa[1])?' ('.$dataa[1]:'').', ';
                }
                $result = substr($build, 0, -2);
                return $result;
            } else {
                return "";
            }
        }

        private function getMangaSerial($html){
            return $this->getSpanLink('dark_text','Serialization:',$html);
        }

        private function getMangaStartDate($html){
            $dt = explode(' to ',$this->getSpanDom('dark_text','Published:',$html));
            if (!empty($dt[0])){
                return (($dt[0] != '?')?date('Y-m-d',strtotime($dt[0])):'0000-00-00'); 
            } else {
                return '0000-00-00';
            }
        }

        private function getMangaEndDate($html){
            $dt = explode(' to ',$this->getSpanDom('dark_text','Published:',$html));
            if (!empty($dt[1])){
                return (($dt[1] != '?')?date('Y-m-d',strtotime($dt[1])):'0000-00-00'); 
            } else {
                return '0000-00-00';
            }
        }

        private function getMetadataManga($html){
            return [
                'genres' => $this->getGenre($html),
                'authors' => $this->getMangaAuthor($html),
                'authors_formatted' => $this->getMangaAuthorFormatted($this->getMangaAuthor($html)),
                'serials' => $this->getMangaSerial($html)
            ];
        }

        /**
         * Grab Manga
         *  
         * @param $id = manga id
         * @return json
         */
        public function grabManga($id){
            header ("Content-Type:application/json");
            $this->grabHTMLManga($id);
            $html = $this->mangaHTML;
            if (!empty($html)){
                $statusdata = ((strpos($this->getTitle($html),'404 Not Found') !== false)?'error':'success');
                if ($statusdata == 'success'){
                    $result = json_encode([
                        'entry' => [
                            'id' => $this->getID($id),
                            'title' => $this->getTitle($html),
                            'english' => $this->getEnglish($html),
                            'japanese' => $this->getJapanese($html),
                            'synonyms' => $this->getSynonyms($html),
                            'chapters' => $this->getMangaChapter($html),
                            'volumes' => $this->getMangaVolume($html),
                            'score' => $this->getScore($html),
                            'type' => $this->getType($html),
                            'status' => $this->getStatus($html),
                            'start_date' => $this->getMangaStartDate($html),
                            'end_date' => $this->getMangaEndDate($html),
                            'synopsis' => $this->getSynopsis($html),
                            'image' => $this->getImage($html)
                        ],
                        'metadata' => $this->getMetadataManga($html),
                        'status' => $statusdata,
                        'message' => 'Data found.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                } else {
                    $result = json_encode([
                        'status' => $statusdata,
                        'message' => 'Data not found.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ]);
                }
            } else {
                $result = json_encode([
                    'status' => 'error',
                    'message' => 'Failed to get any response from MyAnimeList.',
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ]);
            }
            if ($this->pretty){
                return $this->prettyPrint($result);
            } else {
                return $result;
            }
        }

        /* Grabbing Search */
        private function grabHTMLSearch($title,$type='anime'){
            switch (strtolower($type)){
                case "manga":
                    $url = $this->mangagrabsearch.$title;
                    $this->searchlink = $this->mangagrab;
                    break;
                default:
                    $url = $this->animegrabsearch.$title;
                    $this->searchlink = $this->animegrab;
            }
            $this->HTMLSearch = $this->execGetRequest($url);
            $this->HTMLTable = $this->getTableSearch($this->HTMLSearch);
        }

        private function getTableSearch($html){
            if (!empty($html)){
                $tmp = explode('<div class="js-categories-seasonal js-block-list list"><table border="0" cellpadding="0" cellspacing="0" width="100%">',$html);
                if (!empty($tmp[1])){
                    $tmp2 = explode('</table>',$tmp[1]);
                    $tmp3 = explode('</tr><tr>',$tmp2[0]);
                    if (!empty($tmp3[1])){
                        return rtrim(trim($tmp3[1]),'</tr>').'>';
                    } else {
                        return "";
                    }
                } else {
                    return "";
                }
            } else {
                return "";
            }
        }

        private function getTableArray($title,$type){
            $this->grabHTMLSearch($title,$type);
            if (!empty($this->HTMLTable)) return explode('</tr>',$this->HTMLTable);
            return "";
        }

        private function getLinkFromTable($datahtmlpertable){
            $data = explode($this->searchlink,$datahtmlpertable);
            if (!empty($data[1])){
                $data2 = explode('"',$data[1]);
                return $this->searchlink.$data2[0];
            }
            return "";
        }

        private function getIDFromTable($datahtmlpertable){
            $data = explode($this->searchlink,$datahtmlpertable);
            if (!empty($data[1])){
                $data2 = explode('/',$data[1]);
                return $data2[0];
            }
            return "";
        }

        private function getTitleFromTable($datahtmlpertable){
            $data = explode('<strong>',$datahtmlpertable);
            if (!empty($data[1])){
                $data2 = explode('</strong>',$data[1]);
                return $data2[0];
            }
            return "";
        }

        private function getVideoLinkFromTable($datahtmlpertable){
            $link = $this->getLinkFromTable($datahtmlpertable);
            if(!empty($link)){
                return $link.'/video';
            }
            return "";
        }

        private function getImageFromTable($datahtmlpertable){
            $data = explode('data-src="',$datahtmlpertable);
            if (!empty($data[1])){
                $data2 = explode('?',$data[1]);
                return str_replace('/r/50x70','',$data2[0]);
            }
            return "";
        }

        private function getThumbnailFromTable($datahtmlpertable){
            $data = explode('data-src="',$datahtmlpertable);
            if (!empty($data[1])){
                $data2 = explode('"',$data[1]);
                return $data2[0];
            }
            return "";
        }

        private function getDescriptionFromTable($datahtmlpertable){
            $data = explode('<div class="pt4">',$datahtmlpertable);
            if (!empty($data[1])){
                $data2 = explode('</div>',$data[1]);
                return trim(preg_replace('#<a.*?>(.*?)</a>#i', '', $data2[0]));
            }
            return "";
        }

        private function getTypeFromTable($datahtmlpertable){
            $data = explode('<td class="borderClass ac bgColor" width="45">',str_replace(['bgColor0','bgColor1'],'bgColor',$datahtmlpertable));
            if (!empty($data[1])){
                $data2 = explode('</td>',$data[1]);
                return trim($data2[0]);
            }
            return "";
        }

        private function getEpisodeFromTable($datahtmlpertable){
            $data = explode('<td class="borderClass ac bgColor" width="40">',str_replace(['bgColor0','bgColor1'],'bgColor',$datahtmlpertable));
            if (!empty($data[1])){
                $data2 = explode('</td>',$data[1]);
                return trim($data2[0]);
            }
            return "";
        }

        private function getScoreFromTable($datahtmlpertable){
            $data = explode('<td class="borderClass ac bgColor" width="50">',str_replace(['bgColor0','bgColor1'],'bgColor',$datahtmlpertable));
            if (!empty($data[1])){
                $data2 = explode('</td>',$data[1]);
                return trim($data2[0]);
            }
            return "";
        }

        private function builtDataArray($datahtmlpertable,$type='anime',$lite=false){
            if ($lite) return [
                'id' => $this->getIDFromTable($datahtmlpertable),
                'title' => $this->getTitleFromTable($datahtmlpertable),
                'image' => $this->getImageFromTable($datahtmlpertable),
                'url' => $this->getLinkFromTable($datahtmlpertable)
            ];
            switch(strtolower($type)){
                case 'manga':
                    $data = [
                        'type' => $this->getTypeFromTable($datahtmlpertable),
                        'volume' => $this->getEpisodeFromTable($datahtmlpertable),
                        'score' => $this->getScoreFromTable($datahtmlpertable)
                    ];
                    break;
                default:
                    $data = [
                        'type' => $this->getTypeFromTable($datahtmlpertable),
                        'episode' => $this->getEpisodeFromTable($datahtmlpertable),
                        'score' => $this->getScoreFromTable($datahtmlpertable),
                        'video' => $this->getVideoLinkFromTable($datahtmlpertable)
                    ];
                    break;
            }
            return [
                'id' => $this->getIDFromTable($datahtmlpertable),
                'title' => $this->getTitleFromTable($datahtmlpertable),
                'image' => $this->getImageFromTable($datahtmlpertable),
                'thumbnail' => $this->getThumbnailFromTable($datahtmlpertable),
                'url' => $this->getLinkFromTable($datahtmlpertable),
                'description' => $this->getDescriptionFromTable($datahtmlpertable),
                'metadata' => $data
            ];
        }

        private function searchDataJSON($title,$type='anime',$lite=false){
            $data = $this->getTableArray($title,$type);
            if (!empty($data)){
                $list = array();
                foreach ($data as $value){
                    if(!empty($value)) $list[] = $this->builtDataArray($value,$type,$lite);
                }
                if(!empty($list)){
                    $result = [
                        'results' => $list,
                        'status' => 'success',
                        'message' => 'Data found.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ];
                } else {
                    $result = [
                        'status' => 'error',
                        'message' => 'Data not found.',
                        'logger' => [
                            'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                            'timestamp' => date('Y-m-d h:i:s', time())
                        ]
                    ];
                }
            } else {
                $result = [
                    'status' => 'error',
                    'message' => 'Data not found.',
                    'logger' => [
                        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                        'timestamp' => date('Y-m-d h:i:s', time())
                    ]
                ];
            }
            return json_encode($result);
        }

        /**
         * Find Anime
         *  
         * @param $title = the title to search
         * @param $listing = the data will show as listing or single. Default value is false.
         * @return json
         */
        public function findAnime($title,$listing=false){
            header ("Content-Type:application/json");
            if($listing) {
                $resultlist = $this->searchDataJSON(rawurlencode($title),'anime');
                if ($this->pretty){
                    return $this->prettyPrint($resultlist);
                } else {
                    return $resultlist;
                }
            }

            $result = $this->searchDataJSON(rawurlencode($title),'anime',true);
            $data = json_decode($result);
            if (!empty($data) && $data->status == 'success'){
                $id = "";
                foreach($data->results as $item){
                    if($item->title == $title){
                        $id = $item->id;
                    }
                }
                if (empty($id)){
                    $id = $data->results[0]->id;
                }
                return $this->grabAnime($id);
            } else {
                if ($this->pretty){
                    return $this->prettyPrint($result);
                } else {
                    return $result;
                }
            }
        }

        /**
         * Find Manga
         *  
         * @param $title = the title to search
         * @param $listing = the data will show as listing or single. Default value is false.
         * @return json
         */
        public function findManga($title,$listing=false){
            header ("Content-Type:application/json");
            if($listing) {
                $resultlist = $this->searchDataJSON(rawurlencode($title),'manga');
                if ($this->pretty){
                    return $this->prettyPrint($resultlist);
                } else {
                    return $resultlist;
                }
            }

            $result = $this->searchDataJSON(rawurlencode($title),'manga',true);
            $data = json_decode($result);
            if (!empty($data) && $data->status == 'success'){
                $id = "";
                foreach($data->results as $item){
                    if($item->title == $title){
                        $id = $item->id;
                    }
                }
                if (empty($id)){
                    $id = $data->results[0]->id;
                }
                return $this->grabManga($id);
            } else {
                if ($this->pretty){
                    return $this->prettyPrint($result);
                } else {
                    return $result;
                }
            }
        }

    }
?>