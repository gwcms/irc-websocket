
//api
gwws = {
	socket: false,
	pingtimer: false,
	msgid: 0,
	callbacks: {},
	callbackTimeouts: {},
	debug: 1,
	messageCallbacks: {},
	events:  {},
	verbose: true,
	auto_reconnect: true,
	last_connection_info: {},
	reconnect_speed: 3,
	init_reconn_speed: 3,
	
	connect: function (url, user, pass)
	{
		gwws.last_connection_info={ url:url, user:user, pass:pass };
		
		if (gwws.socket)
		{
			return gwws.log('Already connected');
		}


		gwws.socket = new WebSocket(url);
		gwws.socket.onmessage = gwws.onmessage;

		gwws.socket.onopen = function () {

			gwws.fireEvent('connect');

			gwws.pingStart();

			if(gwws.verbose)
				gwws.log('connected');
			
			gwws.reconnect_speed = gwws.init_reconn_speed;
		}


		gwws.socket.onclose = gwws.close;
	},
	auth: function (user, pass) {
		gwws.send('auth', false, JSON.stringify({user: user, pass: pass}))
	},
	generalAction: function (action, data, callback)
	{
		gwws.send0({action: action, data: data},
				function (data) {
					if (!data) {
						var success = false;
						gwws.log(action + ' timeout');
					} else {
						var success = data.data == 'SUCCESS'

						if (success) {
							
							if(gwws.verbose)
								gwws.log(action + ' was successfull');
							
						} else {
							gwws.log(action + ' failed. Errors: ' + JSON.stringify(data.errors));
						}
					}
					
					if(callback)
						callback(success, data);//callback with success state
				},
				10000
				); //10s timeout		
	},
	createuser: function (userdata, callback)
	{
		gwws.generalAction('createuser', userdata, callback);
	},
	authorise: function (userdata, callback)
	{
		gwws.generalAction('authorise', userdata, function(success, data){
			if(success)
				gwws.fireEvent('authorise', data);
			
			if(callback)
				callback(success, data);
		});
		
		
	},
	createchan: function(chandata, callback){
		gwws.generalAction('createchan', chandata, callback);
	},
	
	//chandata {channel: "yourchannel", pass: "SpecifyIfItIsNeeded"}
	joinchan: function(chandata, callback){
		gwws.generalAction('joinchan', chandata, function(success, data){
			if(success)
				gwws.fireEvent('joinchan', data);
			
			if(callback)
				callback(success, data)
		});
	},	
	infochan: function(chandata, callback){
		gwws.generalAction('infochan', chandata, function(success, data){
			if(success)
				gwws.fireEvent('infochan_receive', data);
			
			if(callback)
				callback(success, data)
		});
	},
	chanlist: function(callback){
		gwws.generalAction('chanlist', {}, callback);		
	},
	
	messagechan: function(channel, message, callback){
		gwws.generalAction('messagechan', {channel: channel, message: message}, callback);		
	},
	messageprivate: function(user, message, callback){
		gwws.generalAction('messageprivate', {user: user, message: message}, callback);		
	},
	
	
	onmessage: function (e) {

		if(gwws.verbose)
			gwws.log("Text message received: " + e.data);
		
		var msg = JSON.parse(e.data);

		if (msg.msgid)
			gwws.procCallback(msg.msgid, msg);

		
		gwws.processMessageCallback(msg.action, msg)
		gwws.processMessageCallback('any', msg)
	},
	
	registerMessageCallback: function(action, name, callback){
		
		if(!gwws.messageCallbacks.hasOwnProperty(action))
			gwws.messageCallbacks[action] = {}
		
		gwws.messageCallbacks[action][name] = callback;
	},
	
	processMessageCallback: function(action, msg)
	{
		if(gwws.messageCallbacks.hasOwnProperty(action))
			for(name in gwws.messageCallbacks[action])
				gwws.messageCallbacks[action][name](msg);	
	},
	
	registerEvent: function(event, name, callback){
		if(!gwws.events.hasOwnProperty(event))
			gwws.events[event] = {}
		
		gwws.events[event][name] = callback;		
	},
	fireEvent: function(event, context)
	{
		if(gwws.events.hasOwnProperty(event))
			for(name in gwws.events[event])
				gwws.events[event][name](context);
		
	},
	
	
	close: function () {

		gwws.log("Connection closed.");
		gwws.socket = null;

		gwws.pingStop()

		gwws.fireEvent('disconnect');
		
		if(gwws.auto_reconnect)
		{
			setTimeout(gwws.reconnect, gwws.reconnect_speed*1000);
			//incremental - prevent overloading
			gwws.reconnect_speed++;
		}
	},
	
	reconnect: function()
	{
		gwws.log('Trying reconnect');
		
		gwws.connect(gwws.last_connection_info.url, gwws.last_connection_info.user, gwws.last_connection_info.pass)
		
		//if connection will fail, close event will work
	},
	
	//simlified
	send: function (action, channel, data)
	{
		var msg = {action: action, '#': channel, data: data};

		gwws.send0(msg);
	},
	//low level
	send0: function (data, callback, timeout) {

		if (!gwws.socket) {
			gwws.log('Not connected');
			return false;
		}
		data.msgid = ++gwws.msgid;
		
		gwws.fireEvent('before_message', data);
		
		if(gwws.socket.readyState!=1)
			gwws.log('Connection not open');
		
		gwws.socket.send(JSON.stringify(data));

		if (callback)
			gwws.callbacks[data.msgid] = callback;

		if (timeout)
			gwws.callbackTimeouts[data.msgid] = setTimeout(function () {
				gwws.callbackTimeout(data.msgid)
			}, timeout);
	},
	callbackTimeout: function (msgid) {
		gwws.log('timeout msgid' + msgid);

		gwws.procCallback(msgid, false)
	},
	procCallback: function (msgid, payload) {
		if (gwws.callbacks[msgid]) {
			gwws.callbacks[msgid](payload);
			delete gwws.callbacks[msgid];
			clearTimeout(gwws.callbackTimeouts[msgid])
			delete gwws.callbackTimeouts[msgid];

			//gwws.log("response received");
		}
	},
	disconnect: function ()
	{
		gwws.socket.close();

		gwws.log('Disconnect');
	},
	ping: function () {
		gwws.send0({action: 'ping'}, function (data) {
			//process reply
			if (data) {
				//pong received
			} else {
				gwws.fireEvent('ping_timeout')
			}
		}, 5000);
	},
	pingStart: function () {
		gwws.pingtimer = setInterval(gwws.ping, 60000);
	},
	pingStop: function () {
		clearInterval(gwws.pingtimer);
	},
	log: function (data)
	{
		if (gwws.debug)
			console.log( typeof data == 'string' ? data : JSON.stringify(data));
	},
	testLoad: function(){
		
		for(var i=0;i<100;i++)
			gwws.ping();
			
	}

}
