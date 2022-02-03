<?php

if(!$_COOKIE['user'])
{
	$user = rand(1, 1000000);
	setcookie('user', $user, time()+60*60*24*365);
}
else
{
	$user = $_COOKIE['user'];
	setcookie('secondtime', 1, time()+60*60*24*365);
}

?>
<!DOCTYPE html> 
<html> 
<head> 
	<title>Work&Walk</title> 
	<meta name="viewport" content="user-scalable=no, width=500"> 
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<link rel="apple-touch-icon" href="touch-icon-iphone.png" />
	<link rel="apple-touch-icon" sizes="72x72" href="touch-icon-ipad.png" />
	<link rel="apple-touch-icon" sizes="114x114" href="touch-icon-iphonehd.png" />
	<link rel="apple-touch-icon" sizes="144x144" href="touch-icon-ipadhd.png" />
	<link rel="stylesheet" href="https://code.jquery.com/mobile/1.2.0/jquery.mobile-1.2.0.min.css" />
	<script src="https://code.jquery.com/jquery-1.8.2.min.js"></script>
	<script src="https://code.jquery.com/mobile/1.2.0/jquery.mobile-1.2.0.min.js"></script>
	<style type="text/css">
		.big {font-size: 3em}
		#analyz{text-align: center}
		#analyz img{border: 0}
	</style>
	<script type="text/javascript">
	//settings
	var min_time_between_steps = 400; // 400
	var max_time_between_steps_at_work = 5000; // 5000
	var max_time_between_steps_at_walk = 60000; // 1 minute
	var min_steps_to_switch_to_walk = 7; // 7 during work
	var min_steps_to_detect_walk = 3; // 3 during analyzis
	var min_step_acceleration = 3; // 3
	var work_interval = 40; // 40
	var time_for_analyzis = 5000; // 5000
	var min_steps_per_walk = 50; // 50
	var max_steps_per_walk = 300; // 300
	var one_minute = 60000; // 60000

	// globals
	var state = "analyze";
	var steps = 0;
	var total_steps = 0;
	var minutes = 0;
	var total_minutes = 0;
	var timer;
	var timer_check_stop;
	var sim_timer;
	var last_step_time = false;
	var last_work_minutes = 0;
	var walk_done_shown = false;

	if(!window.DeviceMotionEvent)
	{
		// device without accelerometer
	}
	
	function dom()
	{
		$("#ww").on('slidestop', function(event)
		{
			if($("#ww").val() == "walk" && state == "work")
			{
				startWalk();
				// manually switched to walk
			}
			else if($("#ww").val() == "work" && state == "walk")
			{
				startWork(minutes);
				// manually switched to work
			}
		});
		setInterval(function()
		{
			if($("#blinker")[0].style.visibility == "visible") $("#blinker")[0].style.visibility = "hidden";
			else $("#blinker")[0].style.visibility = "visible";
		}, 500);
		if(window.DeviceMotionEvent)
		{
			addEventListener('devicemotion', stepWatcher, true);
		}
		if(localStorage["state"] == "work")
		{
			$("#analyz").hide();
			$("#selectww").show();
			startWork(localStorage["minutes"]);
		}
		else if(localStorage["state"] == "walk")
		{
			$("#analyz").hide();
			$("#selectww").show();
			startWalk(localStorage["steps"]);
		}
		else
		{
		if(window.DeviceMotionEvent)
		{
			startAnalyse();
		}
		else
		{
			$("#analyz").hide();
			$("#selectww").show();
			startWork();
		}
		}
	}
	function startAnalyse()
	{
		// start analyzis
		setTimeout(function()
		{
			$("#analyz").hide();
			$("#selectww").show();
			if(steps >= min_steps_to_detect_walk)
			{
				startWalk(steps);
				// start walk after analyzis
			}
			else
			{
				startWork();
				// start work after analyzis
			}
		}, time_for_analyzis);
	}
	function startWalk(loc_steps)
	{
		if(!loc_steps)
			steps = 0;
		else
			steps = loc_steps;

		state = "walk";
		$("#ww").val("walk");
		$("#ww").slider("refresh");
		$("#stepcounter").show();
		$("#timer").hide();

		walk_done_shown = false;

		clearInterval(timer);
		total_minutes += minutes;

		$("#steps").html(steps);
		var d = new Date();
		last_step_time = d.getTime();

		localStorage["state"] = state;
		//localStorage["steps"] = steps;

		if(!window.DeviceMotionEvent)
		{
			stepSimulation(true);
		}

		// timer to check if we have stopped walking
		timer_check_stop = setInterval(function()
		{
			var d = new Date();
			var now = d.getTime();
			if(now - last_step_time > max_time_between_steps_at_walk && state == "walk")
			{
				clearInterval(timer_check_stop);
				// detected work during walk
				if(steps < min_steps_per_walk)
				{
					startWork(minutes);
				}
				else
				{
					startWork(1);
				}
			}
		}, 1000);
	}
	function startWork(loc_minutes)
	{
		if(!loc_minutes)
			minutes = 0;
		else
			minutes = loc_minutes;

		state = "work";
		$("#ww").val("work");
		$("#ww").slider("refresh");
		$("#stepcounter").hide();
		$("#timer").show();

		clearInterval(timer_check_stop);
		//clearInterval(timer);
		total_steps += steps;
		steps = 0;

		localStorage["state"] = state;
		//localStorage["minutes"] = minutes;

		if(!window.DeviceMotionEvent)
		{
			stepSimulation(false);
		}

		showTime(minutes);

		// main work timer
		timer = setInterval(function()
		{
			minutes++;
			showTime(minutes);
			localStorage["minutes"] = minutes;

			if(minutes % work_interval == 0 && minutes > 0) // let's walk
			{
				$("#GoWalkDialog").popup("open");
			}
		}, one_minute);
	}
	function showTime(loc_minutes)
	{
		var hours = Math.floor(loc_minutes/60);
		var mins = loc_minutes % 60;
		if(mins < 10) mins = "0" + mins;
		$("#hours").html(hours);
		$("#minutes").html(mins);
	}
	function walkNow()
	{
		$("#GoWalkDialog").popup("close");
		startWalk();
		// pressed I go now button
	}
	function skip()
	{
		$("#GoWalkDialog").popup("close");
		// pressed Skip button
	}
	function checkboxClick(opt)
	{
		var code = opt;
		if($("#option"+opt)[0].checked) code += "1";
		else code += "0";
		$.get('ajax.php', {act: "survey", action: code});
	}
	function stepWatcher(event)
	{
		fullacc = Math.round(Math.sqrt(Math.pow(event.acceleration.x, 2) + Math.pow(event.acceleration.y, 2) + Math.pow(event.acceleration.z, 2)));
		if(fullacc >= min_step_acceleration)
		{
			var d = new Date();
			var now = d.getTime();
			if(!last_step_time || now - last_step_time > min_time_between_steps) // step!
			{
				steps++;
				localStorage["steps"] = steps;
				if(state == "walk")
				{
					$("#steps").html(steps);
					if(steps >= max_steps_per_walk)
					{
						if(!walk_done_shown)
						{
							$("#WalkDone").popup("open");
							walk_done_shown = true;
						}
					}
				}
				else if(state == "work") // detected during work
				{
					if(now - last_step_time > max_time_between_steps_at_work) // if too rare - steps go to 0
					{
						steps = 0;
					}
					if(steps >= min_steps_to_switch_to_walk) // we walk
					{
						startWalk(steps);
						// detected walk during work
					}
				}
				last_step_time = now;
				//$("#acc").html("step! Acc = " + fullacc);
			}
		}
	}
	function stepSimulation(onoff)
	{
		if(onoff)
		{
			sim_timer = setInterval(function()
			{
				steps++;
				$("#steps").html(steps);

				if(steps >= max_steps_per_walk)
				{
					if(!walk_done_shown)
					{
						$("#WalkDone").popup("open");
						walk_done_shown = true;
					}
				}
				if(steps >= min_steps_per_walk)
				{
					minutes = 0;
				}

				var d = new Date();
				var now = d.getTime();
				last_step_time = now;
			}, 700);
		}
		else
		{
			clearInterval(sim_timer);
		}
	}

	$(document).delegate("#main", "pageinit", dom);
	</script>
</head> 
<body>

<?php
if(!$_COOKIE['secondtime']) {
?>
<div data-role="page" id="about">

	<div data-role="header" data-theme="b">
		<h1>Welcome to Work&Walk!</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<p>This application is designed to make you healthier.</p>
		<p>It detects when you walk with your phone or when you don't move for a long time and 
			reminds you to do short breaks in order to give a rest to your eyes and body.</p>
		<p>Please, view and vote for other features that can be implemented in future.</p>
		<p>Thank you and hope that it will make you feel better.</p>
		<a href="#main" data-role="button" data-theme="b">Start using</a>
	</div><!-- /content -->

	<div data-role="footer">
		<h4>Roman Chirikov</h4>
	</div><!-- /footer -->
</div><!-- /page -->
<?php } ?>

<div data-role="page" id="main">

	<div data-role="header" data-theme="b">
		<h1>Work&Walk</h1>
		<a href="#about" data-role="button" data-icon="info" data-iconpos="notext">About</a>
	</div><!-- /header -->

	<div data-role="content">
		<div class="containing-element">

			<div id="analyz">
				<img src="heart.gif"><br>
				We are analysing your activity...
			</div>
			<div id="selectww" style="display: none; text-align: center">
				<select name="flip-min" id="ww" data-role="slider">
					<option value="walk">I walk</option>
					<option value="work" selected="selected">I work</option>
				</select>
			</div>
			<div id="timer" style="display: none">
				<p>You don't move for</p>
				<div style="text-align: center"><span id="hours" class="big">0</span>h <span id="blinker">:</span> <span id="minutes" class="big">00</span>m</div>
			</div>
			<div id="stepcounter" style="display: none">
				<p>You have done</p>
				<div style="text-align: center"><span class="big" id="steps">0</span> steps</div>
			</div>
			<!--<div id="acc">--</div>-->
			<a href="#features" onclick="javascript: $.get('ajax.php', {act: 'survey', action: 0});" data-role="button" data-theme="b" data-icon="arrow-r" data-iconpos="right">View other features</a>

		</div>
	</div><!-- /content -->

	<div data-role="popup" id="GoWalkDialog" data-overlay-theme="b" data-theme="b" style="max-width:360px;" class="ui-corner-all">
		<div data-role="header" data-theme="b" class="ui-corner-top">
			<h1>It is time to walk!</h1>
		</div>
		<div data-role="content" data-theme="b" class="ui-corner-bottom ui-content">
			<div style="text-align: center"><img src="dog2.jpg"></div>
			<h3 class="ui-title">Please give your body and your eyes a <br>5-minutes break.</h3>
			<p>Try to make around 300 steps.</p>
			<a href="javascript: walkNow();" data-role="button" data-inline="true" data-theme="b">I go right now</a>    
			<a href="javascript: skip();" data-role="button" data-inline="true" data-transition="flow" data-theme="c">I skip this time</a>  
		</div>
	</div>

	<div data-role="popup" id="WalkDone" data-overlay-theme="b" data-theme="b" style="max-width:360px;" class="ui-corner-all">
		<div data-role="header" data-theme="b" class="ui-corner-top">
			<h1>Good job!</h1>
		</div>
		<div data-role="content" data-theme="b" class="ui-corner-bottom ui-content">
			<div style="text-align: center"><img src="kubok.jpg"></div>
			<h3 class="ui-title">Now you have minimized potential harm to your health.</h3>
			<p>You can work another 40 minutes.</p>
			<a href="#" data-role="button" data-inline="false" data-transition="flow" data-rel="back" data-theme="b">I'm great</a>  
		</div>
	</div>

</div><!-- /page -->

<div data-role="page" id="features" data-add-back-btn="true">

	<div data-role="header" data-theme="b">
		<h1>Other features</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<p>Please tick those of the features that you think are useful for this application:</p>		
		<fieldset data-role="controlgroup">
			<input type="checkbox" onclick="javascript: checkboxClick(1);" <?php if($_COOKIE[option1] == 1) print "checked" ?> name="option1" id="option1" class="custom" />
			<label for="option1">Share progress with friends/colleagues</label>

			<input type="checkbox" onclick="javascript: checkboxClick(2);" <?php if($_COOKIE[option2] == 1) print "checked" ?> name="option2" id="option2" class="custom" />
			<label for="option2">Create "walking groups" to have breaks together</label>
			
			<input type="checkbox" onclick="javascript: checkboxClick(3);" <?php if($_COOKIE[option3] == 1) print "checked" ?> name="option3" id="option3" class="custom" />
			<label for="option3">Show examples of physical exercises</label>

			<input type="checkbox" onclick="javascript: checkboxClick(4);" <?php if($_COOKIE[option4] == 1) print "checked" ?> name="option4" id="option4" class="custom" />
			<label for="option4">Have a virtual pet to walk with</label>

			<input type="checkbox" onclick="javascript: checkboxClick(5);" <?php if($_COOKIE[option5] == 1) print "checked" ?> name="option5" id="option5" class="custom" />
			<label for="option5">Earn points by going walking and loose them by skipping</label>
	    </fieldset>
	</div><!-- /content -->

</div><!-- /page -->

<?php
if($_COOKIE['secondtime']) {
?>
<div data-role="page" id="about">

	<div data-role="header" data-theme="b">
		<h1>Welcome to Work&Walk!</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<p>This application is designed to make you healthier.</p>
		<p>It detects when you walk with your phone or when you don't move for a long time and 
			reminds you to do short breaks in order to give a rest to your eyes nd body.</p>
		<p>Please, view and vote for other features that can be implemented in future.</p>
		<p>Thank you and hope that it will make you feel better.</p>
		<a href="#main" data-role="button" data-theme="b">Continue using</a>
	</div><!-- /content -->

	<div data-role="footer">
		<h4>Roman Chirikov</h4>
	</div><!-- /footer -->
</div><!-- /page -->
<?php } ?>

</body>
</html>
