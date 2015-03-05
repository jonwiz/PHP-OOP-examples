<?php
class ApiController extends AppController {

   var $components = array('Api');
   var $uses = array('ApiApplication', 'Industry', 'Country', 'City','Venue','Order','SocialData','Booth','MobBanner');

   function beforeFilter() {
      parent::beforeFilter();   

		$this->Auth->allow(array('industries','countries','cities','event','events','users','venues','exhibitors', 'docs','banner','locations'));
		//allow these actions to be non-json HTML
		$html = array('docs');
		
		if(!in_array($this->action, $html)){
			$authResponse = $this->Api->auth();
				if($authResponse["error"])
					die(json_encode($authResponse));
		}

   }   

	function docs() {
	}


	function banner() {
		$industry = (!empty($this->params["named"]["industry"])) ? $this->params["named"]["industry"] : $_POST['industry'];
		$options = array(
			"conditions" => array("MobBanner.industry_id" => $industry),
			"order" => array("MobBanner.id DESC")
		);

		$this->fetch('MobBanner', $options, 'Banner');
	}

	function afterBanner($result){
		if(count($result) > 1){
				  $rand = rand(0,count($result) - 1);
				  foreach($result as $key => $banner){
					  if($key != $rand){
					  	unset($result[$key]);
				  	  }
				  }
		}
		return $result;
	}

	function industries() {
		//$this->Industry->virtualFields["count_events"] = "COUNT(EventIndustry.industry_id)";
		//$this->Industry->virtualFields["update_date"] = "0";
		$options = array(
			"fields" => array("Industry.id", "Industry.name"),
			"joins" => array(
				array("table" => "event_industries","alias" => "EventIndustry","type" => "inner",
					"conditions" => array("EventIndustry.industry_id = Industry.id")
				)
			),
			"group" => array("Industry.id"),
			"order" => array("Industry.name" => "ASC")
		);
		$this->fetch('Industry', $options);
	}

	function countries_old() {
		$this->Country->virtualFields["count_events"] = "COUNT(Event.id)";
		$options = array(
			"fields" => array('Country.id','Country.name','Country.count_events'),
			"joins" => array(
				array("table" => "users",
					"type" => "inner",
					"conditions" => array("users.country = Country.name AND users.country IS NOT NULL")
				),
				array("table" => "events",
					"alias" => "Event",
					"type" => "inner",
					"conditions" => array("users.id = Event.id")
				)
			),
			"group" => array("users.country")
		);
		$this->fetch('Country', $options);
	}


	function venues() {
		$limit = (!empty($this->params["named"]["limit"])) ? $this->params["named"]["limit"] : $_POST['limit'];
		$offset = (!empty($this->params["named"]["offset"])) ? $this->params["named"]["offset"] : $_POST['offset'];
		$city = (!empty($this->params["named"]["city"])) ? $this->params["named"]["city"] : $_POST['city'];
		$country = (!empty($this->params["named"]["country"])) ? $this->params["named"]["country"] : $_POST['country'];

	        /*
		if($country == null && $city == null){
			$response = array();
			$response["error"] = true;
			$response["message"] = "Country or City required to retrieve venues.";
			die(json_encode($response));
		}
		*/

		$this->Venue->virtualFields["update_date"] = "unix_timestamp(Venue.updated)";
		$this->Venue->virtualFields["industry_id"] = "EventIndustry.industry_id";

		$options = array(
			"fields" => array('id','country','city','industry_id','update_date'),
			"joins" => array(
				array("table" => "events","alias" => "Event","type" => "INNER",
					"conditions" => array("Event.venue_id = Venue.id")
				),
				array("table" => "event_industries","alias" => "EventIndustry","type" => "INNER",
					"conditions" => array("Event.id = EventIndustry.event_id")
				)
			),
			"limit" => $limit,
			"offset" => $offset,
			"order" => array("Venue.country")
		);

		/*************** JOIN COUNTRIES *******************/
		if($country != null){
			$countryConditionField = $this->getConditionField("Country", $country);
			
			$country_join = array("table" => "countries",
				"alias" => "Country",
				"type" => "inner",
				"conditions" => array("Country.name = Venue.country AND Country.$countryConditionField = '$country'")
			);
			$options["joins"][] = $country_join;
		}
		/*************** JOIN CITIES *******************/
		if($city != null){
			$cityConditionField = $this->getConditionField("City", $city);
			
			$city_join = array("table" => "cities",
				"alias" => "City",
				"type" => "inner",
				"conditions" => array("City.name = Venue.city AND City.$cityConditionField = '$city'")
			);
			$options["joins"][] = $city_join;
		}

		$this->fetch('Venue', $options);
	}

	 /*
	function afterVenue($result){
		if(count($result)){
				  foreach($result as $key => $venue){
					  $options = array(
						  "fields" => array("Event.id"),
						  "conditions" => array("Event.venue_id" => $result[$key]['Venue']['id'])
					  );
					  $events = $this->Event->find('list',$options);
					  $result[$key]['Venue']['event_ids'] = array_keys($events);	
				  }
		}
		return $result;
	}
	*/

	function cities_prev() {
		$this->City->virtualFields["count_events"] = "count(Event.id)";
		$this->City->virtualFields["update_date"] = strtotime(date("Y-m-d H:i:s"));
		$options = array(
				"fields" => array('City.id', 'City.name', 'City.country_id', 'City.update_date', 'City.count_events'),
				"joins" => array(
					array("table" => "users",
					"alias" => "User",
					"type" => "inner",
					"conditions" => array("User.city = City.name")
				),
					array("table" => "countries",
					"alias" => "Country",
					"type" => "inner",
					"conditions" => array("User.country = Country.name")
				),
					array("table" => "events",
					"alias" => "Event",
					"type" => "inner",
					"conditions" => array("User.id = Event.user_id")
				)
			),
			"group" => array("City.id"),
			"order" => array("City.name")
		);
			
		$this->fetch('City', $options);
	}
	function countries() {
		$this->Venue->virtualFields["industry_id"] = "EventIndustry.industry_id";
		$this->Venue->virtualFields["update_date"] = strtotime(date("Y-m-d H:i:s"));
		$industry = (!empty($this->params["named"]["industry"])) ? $this->params["named"]["industry"] : $_POST['industry'];
		$options = array(
				"fields" => array('Venue.id', 'Venue.country', 'Venue.update_date','Venue.industry_id'),
				"joins" => array(
					array("table" => "events",
					"alias" => "Event",
					"type" => "inner",
					"conditions" => array("Venue.id = Event.venue_id AND Event.start_date > NOW() AND Event.status = 'online'")
				),
					array("table" => "users",
					"alias" => "User",
					"type" => "inner",
					"conditions" => array("User.last_login IS NOT NULL AND User.id = Event.user_id")
				),
				array("table" => "event_industries",
					"alias" => "EventIndustry",
					"type" => "inner",
					"conditions" => array("EventIndustry.event_id = Event.id")
				)
			),
			"group" => array("Venue.country"),
			"order" => array("Venue.country" => 'asc')
		);

		if($industry != null){
			$options["joins"][2]["conditions"][0] .= " AND EventIndustry.industry_id = $industry";
		}
			
		$this->fetch('Venue', $options,'locations');
	}
	function cities() {
		$country = (!empty($this->params["named"]["country"])) ? $this->params["named"]["country"] : $_POST['country'];
		if($country == null){
			$response = array();
			$response["error"] = true;
			$response["message"] = "Country required to retrieve Cities";
			die(json_encode($response));
		}
		$options = array(
				"fields" => array('Venue.id', 'Venue.city'),
				"joins" => array(
					array("table" => "events",
					"alias" => "Event",
					"type" => "inner",
					"conditions" => array("Venue.id = Event.venue_id AND Event.start_date > NOW() AND Event.status = 'online'")
				),
					array("table" => "users",
					"alias" => "User",
					"type" => "inner",
					"conditions" => array("User.last_login IS NOT NULL AND User.id = Event.user_id")
				),
				array("table" => "event_industries",
					"alias" => "EventIndustry",
					"type" => "inner",
					"conditions" => array("EventIndustry.event_id = Event.id")
				)
			),
			"group" => array("Venue.city"),
			"order" => array("Venue.city" => 'asc'),
			"conditions" => array("Venue.country" => $country)
		);

			
		$this->fetch('Venue', $options);
	}
	function afterLocations($result) {
		if(count($result)){
			foreach($result as $key => $Model){
				unset($Model['Venue']['full_address']);
				$result[$key]['Location'] = $Model['Venue'];
				unset($result[$key]['Venue']);
			}
		}
		return $result;
	}


	function event() {
		$id = (!empty($this->params["named"]["id"])) ? $this->params["named"]["id"] : $_POST['id'];
		$this->Event->virtualFields["event_info"] = "Event.long_description";
		//$this->Event->virtualFields["start_datetime"] = "unix_timestamp(Event.start_date)";
		//$this->Event->virtualFields["end_datetime"] = "unix_timestamp(Event.end_date)";
		$this->Event->virtualFields["address"] = "CONCAT(User.address, ' ', User.city, ', ', User.province, ' ', User.postal_code, ' ', User.country)";
		if($id != null){
			$options = array(
				"fields" => array('Event.id', 'Event.event_info','Event.address','Event.logo', 'Event.floor_map','Event.page_name'),
				"joins" => array(
					array("table" => "users",
					"alias" => "User",
					"type" => "inner",
					"conditions" => array("User.id = Event.user_id")
				)
			),
				"conditions" => array("Event.id" => $id)
			);
			$this->Event->contain("SocialData");
			$this->fetch('Event', $options, 'Event');
			return;
		}else{
			$response = array();
			$response["error"] = true;
			$response["message"] = "Event ID required to retrieve Event detail";
			die(json_encode($response));
		}
	}

	function countries_old2() {
		$industry = (!empty($this->params["named"]["industry"])) ? $this->params["named"]["industry"] : $_POST['industry'];
		$this->Event->virtualFields["name"] = "Venue.country";

		//start with basic options, always join users
		$options = array(
			"fields" => array('Event.name'),
			"conditions" => 'Event.id IS NOT NULL',
			"joins" => array(),
			"limit" => $limit,
			"offset" => $offset,
			"order" => array("Event.name"),
			"group" => array("Event.name")
		);

		$join_users = array("table" => "users",
				"alias" => "User",
				"type" => "inner",
				"conditions" => array("User.id = Event.user_id")
			);

		$join_venues = array("table" => "venues",
				"alias" => "Venue",
				"type" => "inner",
				"conditions" => array("User.id = Venue.user_id AND Venue.country = User.country")
			);

		/*************** JOIN INDUSTRIES *******************/
		if($industry != null){
			$industryConditionField = $this->getConditionField("Industry", $industry);

			$joins_industry = array(
				array("table" => "industries",
					"alias" => "Industry",
					"type" => "inner",
					"conditions" => array("Industry.$industryConditionField = '$industry'")
				),
				array("table" => "event_industries",
					"alias" => "EventIndustry",
					"type" => "inner",
					"conditions" => array("EventIndustry.event_id = Event.id")
				)
			);
			array_push($options["joins"], $joins_industry[0], $joins_industry[1]);
		}else{
			$response = array();
			$response["error"] = true;
			$response["message"] = "Industry is required to retrieve countries.";
			die(json_encode($response));
		}

		array_push($options["joins"], $join_users, $join_venues);

		$this->fetch('Event', $options, 'Countries');
	}


	function afterCountries($result) {
		if(count($result)){
			foreach($result as $key => $Model){
				$result[$key]['Country'] = $Model['Event'];
				unset($result[$key]['Event']);
			}
		}
		return $result;
	}


	function events() {
		$city = (!empty($this->params["named"]["city"])) ? $this->params["named"]["city"] : $_POST['city'];
		$country = (!empty($this->params["named"]["country"])) ? $this->params["named"]["country"] : $_POST['country'];
		$industry = (!empty($this->params["named"]["industry"])) ? $this->params["named"]["industry"] : $_POST['industry'];
		$search = (!empty($this->params["named"]["search"])) ? $this->params["named"]["search"] : $_POST['search'];
		$venue = (!empty($this->params["named"]["venue"])) ? $this->params["named"]["venue"] : $_POST['venue'];
		$limit = (!empty($this->params["named"]["limit"])) ? $this->params["named"]["limit"] : $_POST['limit'];
		$offset = (!empty($this->params["named"]["offset"])) ? $this->params["named"]["offset"] : $_POST['offset'];


		$this->Event->virtualFields["update_date"] = "unix_timestamp(Event.updated)";
		$this->Event->virtualFields["start_datetime"] = "unix_timestamp(Event.start_date)";
		$this->Event->virtualFields["end_datetime"] = "unix_timestamp(Event.end_date)";
		$this->Event->virtualFields["city"] = "Venue.city";
		$this->Event->virtualFields["country"] = "Venue.country";

		$options = array(
			'city' => $city,
			'country' => $country,
			'industry_id' => $industry,
			'limit' => $limit,
			'offset' => $offset,
			'fields' => array('Event.id', 'Event.logo', 'Event.title','Event.update_date','Event.start_datetime', 'Event.end_datetime', 'Event.country', 'Event.city'),
		);


		$full_options = $options;
		$full_options["limit"] = 0;
		$full_options["offset"] = 0;
		$total = count($this->Event->getEvents($full_options));
		$result = $this->Event->getEvents($options);
		$result = $this->afterEvents($result);
		$this->set("result", array("total" => $total, "events" => $result));
		$this->view = 'result';

	}


	function events_old() {
		$city = (!empty($this->params["named"]["city"])) ? $this->params["named"]["city"] : $_POST['city'];
		$country = (!empty($this->params["named"]["country"])) ? $this->params["named"]["country"] : $_POST['country'];
		$industry = (!empty($this->params["named"]["industry"])) ? $this->params["named"]["industry"] : $_POST['industry'];
		$search = (!empty($this->params["named"]["search"])) ? $this->params["named"]["search"] : $_POST['search'];
		$venue = (!empty($this->params["named"]["venue"])) ? $this->params["named"]["venue"] : $_POST['venue'];
		$limit = (!empty($this->params["named"]["limit"])) ? $this->params["named"]["limit"] : $_POST['limit'];
		$offset = (!empty($this->params["named"]["offset"])) ? $this->params["named"]["offset"] : $_POST['offset'];

		if($limit == null)
			$limit = 10;
		if($venue != null){
			$options = array(
				"conditions" => array("Event.venue_id" => $venue)
			);
			$this->fetch('Event', $options);
			return;
		}

		if($city == null && $country == null && $industry == null){
			$response = array();
			$response["error"] = true;
			$response["message"] = "Industry, Country or City is required to retrieve events";
			die(json_encode($response));
		}

		$this->Event->virtualFields["update_date"] = "unix_timestamp(Event.updated)";
		$this->Event->virtualFields["start_datetime"] = "unix_timestamp(Event.start_date)";
		$this->Event->virtualFields["end_datetime"] = "unix_timestamp(Event.end_date)";
		if($city != null){
		  $this->Event->virtualFields["city"] = "Venue.city";
		}
		if($country != null){
		  $this->Event->virtualFields["country"] = "Venue.country";
		}


		//start with basic options, always join users
		$options = array(
			"fields" => array('Event.id', 'Event.logo', 'Event.title','Event.update_date','Event.start_datetime', 'Event.end_datetime'),
			"conditions" => "Event.id IS NOT NULL AND Event.status = 'online' AND Event.start_date > NOW()",
			"joins" => array(),
			"limit" => $limit,
			"offset" => $offset,
			"order" => array("Event.start_date" => 'asc', 'Event.id' => 'desc'),
			"group" => array("Event.id")
		);

		$join_users = array("table" => "users",
				"alias" => "User",
				"type" => "inner",
				"conditions" => array("User.id = Event.user_id")
			);

	       if($city != null || $country != null){
		  $options["fields"] += array('Event.country', 'Event.city');
		$join_venues = array("table" => "venues",
				"alias" => "Venue",
				"type" => "inner",
				"conditions" => array("Event.venue_id = Venue.id")
			);
	       }

		/*************** JOIN CITIES *******************/
		if($city != null){
			$join_venues["conditions"][0] .= " AND Venue.city = '$city'";
		}
		/*************** JOIN COUNTRIES *******************/
		if($country != null){
			$join_venues["conditions"][0] .= " AND Venue.country = '$country'";
		}

		/*************** JOIN INDUSTRIES *******************/
		if($industry != null){
			$this->Event->virtualFields["event_industry"] = "Industry.name";
			$this->Event->virtualFields["industry_id"] = "EventIndustry.industry_id";
			$options["fields"][] = 'Event.event_industry';
			$options["fields"][] = 'Event.industry_id';

			$joins_industry = array(
				array("table" => "event_industries",
					"alias" => "EventIndustry",
					"type" => "inner",
					"conditions" => array("EventIndustry.industry_id = '$industry' AND EventIndustry.event_id = Event.id")
				),
				array("table" => "industries",
					"alias" => "Industry",
					"type" => "inner",
					"conditions" => array("Industry.id = EventIndustry.industry_id")
				)
			);
			array_push($options["joins"], $joins_industry[0], $joins_industry[1]);
		}

		array_push($options["joins"], $join_users, $join_venues);

		//$options["conditions"] = $this->getSearchConditions("Event", $search);
		$full_options = $options;
		$full_options["limit"] = 0;
		$full_options["offset"] = 0;
		$total = count($this->Event->find('all', $full_options));
		$result = $this->fetch('Event', $options, 'Events', true);
		$this->set("result", array("total" => $total, "events" => $result));
		$this->view = 'result';
	}

	function afterEvent($result){
		if(count($result)){
			//unset full_address
			foreach($result as $key => $Model){
				//page_name
				if($Model['Event']["page_name"] == null){
					$result[$key]['Event']['event_url'] = "http://examplewebsite.com/events/view/" . $Model['Event']['id'];
				}else{
					$result[$key]['Event']['event_url'] = "http://examplewebsite.com/!/" . $Model['Event']['page_name'];
				}
				unset($result[$key]['Event']['page_name']);
				//floor_map
				if($Model['Event']["floor_map"] == null){
					//$result[$key]['Event']['floor_map'] = "http://examplewebsite.com/slir/320/430/no-image.png";
					$result[$key]['Event']['floor_map'] = null;
				}else{
					$result[$key]['Event']['floor_map'] = "http://examplewebsite.com/slir/320/430/" . $Model['Event']['floor_map'];
				}
				//logo
				if($Model['Event']["logo"] == null){
					//$result[$key]['Event']['small_logo'] = "http://examplewebsite.com/slir/80/80/no-image.png";
					//$result[$key]['Event']['event_logo'] = "http://examplewebsite.com/slir/320/114/no-image.png";
					//$result[$key]['Event']['small_logo'] = null;
					//$result[$key]['Event']['event_logo'] = null;
				}else{
					//$result[$key]['Event']['small_logo'] = "http://examplewebsite.com/slir/80/80/" . $Model['Event']['logo'];
					//$result[$key]['Event']['event_logo'] = "http://examplewebsite.com/slir/320/114/" . $Model['Event']['logo'];
				}
				unset($result[$key]['Event']['logo']);
				//SocialData
				if(!empty($Model['SocialData'])){
					if(count($Model['SocialData'])){
						foreach($Model['SocialData'] as $sc){
							$social_key = $sc['social'] . "_url";
							$result[$key]['Event'][$social_key] = $sc['page_url'];
						}
					}
					//remove SocialData key
					unset($result[$key]['SocialData']);
				}
			}
		}
		return $result;
	}

	function afterEvents($result){
		if(count($result)){
			//unset full_address
			//echo "<pre>" . print_r($result) . "</pre>";
			foreach($result as $key => $Model){
				if($Model['Event']["logo"] == null){
					//$result[$key]['Event']['small_logo'] = "http://examplewebsite.com/slir/80/80/no-image.png";
					$result[$key]['Event']['small_logo'] = null;
				}else{
					$result[$key]['Event']['small_logo'] = "http://examplewebsite.com/slir/80/80/" . $Model['Event']['logo'];
				}
				unset($result[$key]['Event']['full_address']);
				unset($result[$key]['Event']['logo']);
				unset($result[$key]['Venue']);
			}
		}
		return $result;
	}

	function users() {
		$limit = (!empty($this->params["named"]["limit"])) ? $this->params["named"]["limit"] : $_POST['limit'];
	  	$offset = (!empty($this->params["named"]["offset"])) ? $this->params["named"]["offset"] : $_POST['offset'];
		$user = (!empty($this->params["named"]["id"])) ? $this->params["named"]["id"] : null;
		$city = (!empty($this->params["named"]["city"])) ? $this->params["named"]["city"] : null;
		$search = (!empty($this->params["named"]["search"])) ? $this->params["named"]["search"] : null;
		$industry = (!empty($this->params["named"]["industry"])) ? $this->params["named"]["industry"] : null;


		//if user id found, just get them and return
		if($user != null){
			$this->fetch('User', array("conditions" => array("User.id" => $user)));
			return;
		}

		$options = array(
			"limit" => $limit,
			"offset" => $offset
		);

		//if city is blank, find all users
		if($city != null){
				  $cityConditionField = $this->getConditionField("City", $city);
				  $options = array(
					  "joins" => array(
						  array("table" => "cities",
							  "alias" => "City",
							  "type" => "inner",
							  "conditions" => array("City.name = User.city AND City.$cityConditionField = '$city'")
						  )
					  ),
					  "order" => array("User.company_name")
				  );
		}
		/*************** JOIN INDUSTRIES *******************/
		if($industry != null){
			$industryConditionField = $this->getConditionField("Industry", $industry);

			$joins_industry = array(
				array("table" => "industries",
					"alias" => "Industry",
					"type" => "inner",
					"conditions" => array("Industry.$industryConditionField = '$industry'")
				),
				array("table" => "user_industries",
					"alias" => "UserIndustry",
					"type" => "inner",
					"conditions" => array("UserIndustry.user_id = User.id AND Industry.id = UserIndustry.industry_id")
				)
			);
			//if city added joins already, then push industry joins
			if(!empty($options["joins"])){
				array_push($options["joins"], $joins_industry[0], $joins_industry[1]);
			}else{
				//otherwise, just assign the industry joins
				$options["joins"] = $joins_industry;
			}
		}
		$options["conditions"] = $this->getSearchConditions("User", $search);

		$this->fetch('User', $options);
	}


	function exhibitors() {
		$limit = (!empty($this->params["named"]["limit"])) ? $this->params["named"]["limit"] : $_POST['limit'];
		$offset = (!empty($this->params["named"]["offset"])) ? $this->params["named"]["offset"] : $_POST['offset'];
		$event = (!empty($this->params["named"]["event"])) ? $this->params["named"]["event"] : $_POST['event'];
		if($event == null){
			$response = array();
			$response["error"] = true;
			$response["message"] = "Event ID required to retrieve exhibitors.";
			die(json_encode($response));
		}

		
		$this->Order->virtualFields["id"] = "User.id";
		$this->Order->virtualFields["name"] = "User.company_name";
		$this->Order->virtualFields["booth"] = "Order.booth_number";
		$options = array(
			"fields" => array('Order.id', 'Order.name', 'Order.booth', 'Order.event_id'),
			"joins" => array(
				array("table" => "users",
					"alias" => "User",
					"type" => "inner",
					"conditions" => array("User.id = Order.from_user_id")
				)
			),
			"conditions" => array("Order.event_id = $event"),
			"limit" => $limit,
			"offset" => $offset,
		);
		$this->fetch('Order', $options, 'Exhibitors');
	}

	function afterExhibitors($result) {
		if(count($result)){
			foreach($result as $key => $Model){
				$result[$key]['Exhibitor'] = $Model['Order'];
				unset($result[$key]['Order']);
			}
		}
		return $result;
	}

	function fetch($model, $options = array(), $after = NULL, $return = NULL) {
		//fetches from model using options
		$result = $this->$model->find('all', $options);
		/*
		echo " count: " . count($result);
		die;
		*/
		
		if($after != NULL){
			$func = "after" . ucfirst($after);
			$result = $this->$func($result);
		}

	       $log = $this->$model->getDataSource()->getLog(false, false);
	       file_put_contents('log.txt', print_r($log, true));

		//outputs the result to json using Views/Api/json/result.ctp
		if(!count($result))
			$result = array();

	        if($return)
		  return $result;
		$this->set("result", $result);
		$this->view = 'result';
	}

	/* determines whether numeric or string
	* then it returns the field to match the city
	* either id or name
	*/

	function getConditionField($model, $value) {
	 	//int? find ID, string? find name
	 	if(is_numeric($value)){
	 	   $key = 'numeric';
	 	}else if(is_string($value)){
	 	   $key = 'string';
	 	}
		$col = array();
		switch($model){
			case "City":
			case "Industry":
			default:
				$col['numeric'] = 'id';
				$col['string'] = 'name';
			break;
		}
		return $col[$key];
	}


	function getSearchConditions($model, $search) {
		/***************** SEARCH CONDITIONS *****************/
		$c = array();
		if($search != null && $model != null){
			if($model == "Event"){
				$c = array(
					"OR" => array(
						"$model.title LIKE" => "%$search%",
						"$model.long_description LIKE" => "%$search%"
					)
				);
			}else if($model == "User"){
				$c = array(
					"OR" => array(
						"$model.company_name LIKE" => "%$search%",
						"$model.long_description LIKE" => "%$search%"
					)
				);
			}
		}
		return $c;
	}
}

