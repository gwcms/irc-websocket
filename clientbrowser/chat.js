

Date.prototype.getFullMinutes = function () {
	if (this.getMinutes() < 10) {
		return '0' + this.getMinutes();
	}
	return this.getMinutes();
};
Date.prototype.getFullSeconds = function () {
	if (this.getSeconds() < 10) {
		return '0' + this.getSeconds();
	}
	return this.getSeconds();
};


//implementation
gwchat = {
	authorised: false,
	connected: false,
	rooms: [{}, {}], //0-channels 1-privates
	activeroom: 'system',
	serverurl: '',
	username: '',	
	
	init: function ()
	{
		//register callbacks

		gwws.registerMessageCallback('any', 'systemmessages', function (data) {
			gwchat.msg(false, data)
		});

		gwws.registerMessageCallback('leavechan', 'systemmessages', function (data) {
			gwchat._infoChan(data.channel)
		})
		gwws.registerMessageCallback('joinchan', 'systemmessages', function (data) {
			gwchat._infoChan(data.channel)
		});

		gwws.registerMessageCallback('messageprivate', 'systemmessages', function (data) {

			gwchat.addRoom(data.user, 1);
		});




		gwws.registerEvent('connect', 'main', function () {
			gwchat.connected = true;
			gwchat.displayElements();


			gwchat.chanList();

			//gwchat.basicTabAction('authorise', {username: 'labas', pass: '123456'}, 'Authorise');
			//autologin
			gwchat._authorise(false, false, true);

		});

		gwws.registerEvent('disconnect', 'main', function () {

			gwchat.connected = false;
			gwchat.authorised = false;
			gwchat.displayElements();			
		});

		gwws.registerEvent('before_message', 'main', function (data) {
		
			//gwchat.msg(true, { user: gwchat.username, data: data.data.message, channel: data.data.channel});
			gwchat.msg(true, data)
		});

		gwws.registerEvent('ping_timeout', 'main', function (data) {
			gwchat.msg(true, {data: 'Ping timeout | server can be busy'})
		});

		gwws.registerEvent('joinchan', 'main', function (data) {


			if (data.channel)
				gwchat.addRoom(data.channel)


		});


		gwws.registerEvent('authorise', 'main', function (data) {


			gwchat.authorised = true;
			gwchat.displayElements();
			gwchat.username = data.user;

			/*
			gwchat._joinChan('test', '', function () {

				gwchat.messageChan('test', 'labas 123');

			})
			*/
		});

		gwws.registerEvent('infochan_receive', 'main', function (data) {

			var users = [];

			var room = data.info.channel;

			$('#' + room + 'UsersDrop').html('');

			for (var i in data.info.listusers) {
				var name = data.info.listusers[i].name;
				var title = (data.info.listusers[i].isadmin ? '@' : '') + name;
				users.push(name);

				var isme = name == gwchat.username
				$('#' + room + 'UsersDrop').append($('<li><a href="#" data-name="' + name + '" class="' + (isme ? 'userme' : '') + '">' + title + '</a>'));


			}

			gwchat.msg(false, {data: '#' + data.info.channel + ' users: ' + users.join(',')})
			gwchat.initRooms();



		});

		//solved inside before_message
		//gwws.registerMessageCallback('messagechan','main', function(data){
		//	
		//});
		
		
		$('#roomInput').keypress(function (e) {
			//teksto ivestis
			
			if (e.which == 13 && this.value) {

				gwchat.sendMessage();
			}
		})
		$('#roomInputSend').click(function(){ 
			gwchat.sendMessage();
		})
	},
	
	sendMessage: function()
	{
		gwchat.messageRoom(gwchat.activeroom, $('#roomInput').val())
		$('#roomInput').val("")
	},
	
	displayElements: function ()
	{

		var hide = gwchat.connected ? '.disconnected' : '.connected';
		var show = gwchat.connected ? '.connected' : '.disconnected';

		$(hide).fadeOut();
		$(show).fadeIn();

		var hide = gwchat.authorised ? '.notauthorised' : '.authorised';
		var show = gwchat.authorised ? '.authorised' : '.notauthorised';

		$(hide).fadeOut();
		$(show).fadeIn();
	},	
	
	connect: function ()
	{
		var url = $('#serverurl').val();
		var user = $('#user').val();
		var pass = $('#usrpass').val();

		gwchat.serverurl = url;

		gwws.connect(url, user, pass);
	},
	
	msg: function (isout, data)
	{
				
		var room = data.hasOwnProperty('channel') ? data.channel : 'system';
		var action = data.action

		var d = new Date();
		var timestr = d.getHours() + ':' + d.getFullMinutes() + ':' + d.getFullSeconds();

		if (!isout && action == 'messageprivate')
			room = data.user;

		gwchat.addRoom(room)
		gwchat.roomUnseenAdd(room)
		
		
		console.log([data, room]);


		$('#' + room + 'Drop').append($('<div class="' + (isout ? 'outmsg' : 'inmsg') + '" >' +
				'<span class="msgtime">' + timestr + '</span>' +
				(action && room == 'system' ? '<span class="msgact">' + action + '</span>' : '') +
				(data.hasOwnProperty('user') ? '<span class="msguser">&lt;' + data.user + '&gt</span>' : '') +
				(data.hasOwnProperty('data') ? gwchat.escapeHtml(data.data) : "<span class='msgdetails'>" + JSON.stringify(data) + "</span>") +
				'</div>').attr('title', JSON.stringify(data)));
			
			
		gwchat.autoScroll(false);


		//console.log(JSON.stringify({'windowheight':$(window).height(), 'chatbottom-offset-top':$('#chatbottom').offset().top, 'soheight-will-be': $(window).height() - $('#chatbottom').offset().top}));
		//$('#chatbottom').height( $(window).height() - $('#chatbottom').offset().top );g
	},
	
	createUser: function () {
		var user = $('#user').val();
		var pass = $('#pass').val();

		gwchat.basicTabAction('createuser', {username: user, pass: pass, expires: "1 year"}, 'Create User')
	},
	
	_authorise: function (user, pass, remember) {

		if (user && pass && remember) {
			//idet i cookie uzkoduota slaptazodi
			var al = JSON.stringify({'id1': user, 'id2': pass});
			al = CryptoJS.AES.encrypt(al, gwchat.serverurl);
			gwcookie.setCookie('autol', al, 90);
		}
		if (!user && !pass && remember) {
			
			//istraukt is kookio slaptazodi
			var al = gwcookie.getCookie('autol');
			
			//alert(document.cookie);
			
			if (!al)
				return console.log('no cookie');

			al = CryptoJS.AES.decrypt(al, gwchat.serverurl).toString(CryptoJS.enc.Utf8);

			try {
				al = JSON.parse(al);
				user = al.id1
				pass = al.id2
			} catch (err) {

			}

			if (!user || !pass)
				return console.log('Autologin not successful');
		}


		gwchat.basicTabAction('authorise', {username: user, pass: pass}, 'Authorise');
	},
	
	authorise: function ()
	{
		var user = $('#authuser').val();
		var pass = $('#authpass').val();
		var remember =  $('#authremember').is(':checked');

		gwchat._authorise(user, pass, remember)
		return false;
	},
	
	createChan: function ()
	{
		var name = $('#channame').val();
		var pass = $('#chanpass').val();

		gwchat.basicTabAction('createchan', {channel: name, pass: pass}, 'Create Channel', function (data) {

			if (data.data == 'SUCCESS')
			{
				gwchat._joinChan(name, pass, false, 1);
			}

		});
	},
	
	_infoChan: function (name, callback)
	{
		gwchat.basicTabAction('infochan', {channel: name}, 'Info Channel', callback);
	},
	
	_joinChan: function (name, pass, callback, activate)
	{
		gwchat.basicTabAction('joinchan', {channel: name, pass: pass}, 'Join Channel', function () {
			gwchat._infoChan(name)

			if (callback)
				callback();

			if (activate)
				gwchat.roomSwitch(name);
		});
	},
	
	joinChan: function () {
		var name = $('#joinname').val();
		var pass = $('#joinpass').val();



		gwchat._joinChan(name, pass, false, 1);

	},
	
	chanList: function () {
		gwws.chanlist(function (success, data) {
			if (data)
			{
				var str = 'Registered channels list: ';

				for (var i in data.data) {
					str += '#' + data.data[i].channel + (data.data[i].members ? '(' + data.data[i].members + ')' : '') + ' ';
				}

				gwchat.msg(true, {data: str});
			}
		});
	},
	
	messageChan: function (channel, msg)
	{
		gwws.messagechan(channel, msg);
	},
	
	messageRoom: function (room, msg) {

		if (gwchat.isPrivateRoom(room))
		{
			gwws.messageprivate(room, msg);

			//alert('bibis raibas');
			//gwchat.msg(true, { user: gwchat.username, data: data.data.message, channel: data.data.channel});
			
			//alert(JSON.stringify({room: room, data: msg, user: gwchat.username}));
		} else {
			gwws.messagechan(room, msg);
		}
		
		gwchat.msg(true, {channel: room, data: msg, user: gwchat.username})
	},
	
	basicTabAction: function (action, data, title, successcallback) {

		gwws[action](data, function (success, data) {
			if (data) {
				if (success) {
					if (successcallback)
						successcallback(data)

					//gwchat.msg(true, {data: title+' successfull'})
					gwchat.tabOff();
				} else {
					gwchat.msg(true, {data: title + ' failed. Errors: ' + JSON.stringify(data.errors)})
				}
			} else {
				gwchat.msg(true, {data: title + ' timeout'})
			}
		});
	},
	
	tabSwitch: function (name) {
		$('.forms').fadeIn();
		$('.tabs').fadeOut();
		$('#tab-' + name).fadeIn();
	},
	
	tabOff: function ()
	{
		$('.tabs').fadeOut();
		$('.forms').fadeOut();
	},
	
	roomSwitch: function (room)
	{
		gwchat.activeroom = room;

		$('.roomtr').removeClass('active')
		$('.roomtr-' + room).addClass('active');
		gwchat.roomUnseen(room, 0);


		$('.messagesDrop').hide();
		$('#' + room + 'Drop').show();

		$('.usersDrop').hide();
		$('#' + room + 'UsersDrop').show();
		
		//nuimt nebaigta rasyt teksta is inputo
		//uzloadint nuimta teksta

	},
	
	addRoom: function (room, private)
	{
		if (!private)
			private = 0;

		if ($('#' + room + 'Drop').length == 0) {

			gwchat.rooms[private][room] = {unseen: 0, private: private};

			var roomtitle = room == 'system' ? 'System' : (private ? '' : '#') + room;
			var active = room == 'system' ? 'active' : '';
			
			$("#messagecontent").append($('<div class="messagesDrop" id="' + room + 'Drop" ' + (active ? '' : 'style="display:none"') + '></div>'));
			

			if (!private)
				$("#userscontent").append($('<ul class="usersDrop nav nav-pills nav-stacked" id="' + room + 'UsersDrop" style="display:none" ></ul>'));

			$('#roomslist').append('<li class="roomtr roomtr-' + room + ' ' + active + '"><a href="#" data-room="' + room + '" class="roomTrA">' + roomtitle + '<span class="unseen"></span></a></li>');

			gwchat.initRooms();
		}
	},
	
	initRooms: function () {


		$('.roomTrA:not([data-init="1"])').click(function (e) {
			gwchat.roomSwitch($(this).data('room'));
		}).data('init', 1);

		$('.usersDrop li a:not([data-init="1"])').click(function (e) {

			e.preventDefault();
			gwchat.addRoom($(this).data('name'), 1);
			gwchat.roomSwitch($(this).data('name'))

			return false;
		}).data('init', 1);
	},
	
	isPrivateRoom: function (room)
	{
		if (gwchat.rooms[1].hasOwnProperty(room))
			return 1;

		return 0;
	},
	
	roomUnseen: function (room, val)
	{
		$('.roomtr-' + room + ' .unseen').text(val ? ' (' + val + ')' : '');
	},
	
	roomUnseenAdd: function (room)
	{
		var private = gwchat.isPrivateRoom(room);

		if (gwchat.activeroom == room)
			gwchat.rooms[private][room].unseen = 0;
		else
			gwchat.rooms[private][room].unseen++;

		gwchat.roomUnseen(room, gwchat.rooms[private][room].unseen)

	},
	
	disconnect: function ()
	{
		gwcookie.setCookie('autol', '', 0);
		gwws.disconnect();
		
		//alert(document.cookie);
	},
	
	entityMap: {
		"&": "&amp;",
		"<": "&lt;",
		">": "&gt;",
		'"': '&quot;',
		"'": '&#39;',
		"/": '&#x2F;'
	},
	escapeHtml: function (string) {
		return String(string).replace(/[&<>"'\/]/g, function (s) {
			return gwchat.entityMap[s];
		});
	},
	
	autoscroll: true,
	
	autoScrollCheck: function ()
	{
		var ta =  $("#messagecontent")
		var scroll_height = ta[0].scrollHeight;
		var scroll_top = ta.scrollTop();
		var scroll_up = scroll_top - scroll_height + ta.height();

		//debug
		//this.statusObj.append('<span>scoll: '+scroll_up+'</span>');		

		if (scroll_up < -100 && gwchat.autoscroll) {
				gwchat.autoScrollChange(false);
		} else if (scroll_up > -100 && !gwchat.autoscroll) {
				gwchat.autoScrollChange(true);
		}
	},
	
	//enablint disablint autoscroll
	autoScrollChange: function (val)
	{
			//this.statusObj.append('<span>Auto scoll: ' + (val ? 'on' : 'off') + '</span>');
			console.log('Auto scoll: ' + (val ? 'on' : 'off'));
			gwchat.autoscroll = val;
	},

	//jei autoscroll ijungta tada nuskrolint contenta i apacia
	autoScroll: function (first)
	{
		var ta =  $("#messagecontent")
		var scroll_height = ta[0].scrollHeight;

		if (first || gwchat.autoscroll)
			ta.scrollTop(scroll_height + 1000);
	}	
	
}


gwcookie = {
	setCookie: function (cname, cvalue, exdays)
	{
		var d = new Date();
		d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
		var expires = "expires=" + d.toUTCString();
		document.cookie = cname + "=" + cvalue + "; " + expires;
	},
	getCookie: function (cname)
	{
		var name = cname + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) == 0) {
				return c.substring(name.length, c.length);
			}
		}
		return "";
	}
}


function runTests()
{

}





$(function () {
	gwchat.init();
	gwchat.connect();
})