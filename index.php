<?php

include __DIR__.'/init.php';
include __DIR__.'/clientbrowser/lang.php';

$url = GW::s('WSS/IRCURI');


?>

<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">

		<script src="https://use.fontawesome.com/49ec075096.js"></script>

		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.js"></script>
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/aes.js"></script>




		<script src="clientbrowser/chat.js" type="text/javascript"></script>
		<script src="clientbrowser/gwws.js" type="text/javascript"></script>
	
		<link rel="stylesheet" href="clientbrowser/chat.css" type="text/css" />

	</head>

	<body>
		<div class="container">

			<div class="chattop">
				<div class="panel connected">	



					<div class="btn-group">
						<button type="button" class="notauthorised btn btn-default" data-toogle="button" onclick="gwchat.tabSwitch('create')"><i class="fa fa-plus"></i> <?php echo $lang['CREATE_USER'];?></button>
						<button type="button" class="notauthorised btn btn-default" data-toogle="button" onclick="gwchat.tabSwitch('authorise')"><i class="fa fa-sign-in"></i> <?php echo $lang['AUTHORISE'];?></button>
					</div>
					<div class="btn-group">
						<button type="button" class="authorised btn btn-default" data-toogle="button" onclick="gwchat.tabSwitch('createchan')"><i class="fa fa-plus"></i> <?php echo $lang['CREATE_CHANEL'];?></button>
						<button type="button" class="authorised btn btn-default" data-toogle="button" onclick="gwchat.tabSwitch('joinchan')"><i class="fa fa-sign-in"></i> <?php echo $lang['JOIN_CHANEL'];?></button>
					</div>
					<button type="button" class="btn btn-default" data-toogle="button" onclick="gwchat.disconnect()"><i class="fa fa-chain-broken"></i> <?php echo $lang['DISCONNECT'];?></button>

				</div>

				<div class="forms">
					<div class="tabs" id="tab-create">

						<div class="panel panel-primary">
							<div class="panel-heading"><?php echo $lang['CREATE_USER'];?></div>
							<div class="panel-body">

								<form class="form-horizontal" role="form" onsubmit="gwchat.createUser();return false">
									<div class="form-group">
										<label for="user" class="control-label col-sm-2"><?php echo $lang['USER'];?> *</label>
										<div class="col-sm-4"><input id="user" class="form-control" required /></div>
									</div>	
									<div class="form-group">
										<label for="pass" class="control-label col-sm-2"><?php echo $lang['PASSWORD'];?> *</label>
										<div class="col-sm-4"><input id="pass" type="pass" class="form-control" required /></div>
									</div>
									<div class="form-group">        
										<div class="col-sm-offset-2 col-sm-10">
											<button type="submit" class="btn btn-default"><i class="fa fa-check"></i> <?php echo $lang['SUBMIT'];?></button>
										</div>
									</div>										

								</form>								

							</div>
						</div>

					</div>
					
					<div class="tabs" id="tab-authorise">

						<div class="panel panel-primary">
							<div class="panel-heading"><?php echo $lang['AUTHORISE'];?></div>
							<div class="panel-body">

								<form class="form-horizontal" role="form" onsubmit="gwchat.authorise();return false">
									<div class="form-group">
										<label for="user" class="control-label col-sm-2"><?php echo $lang['USER'];?> *</label>
										<div class="col-sm-4"><input id="authuser" class="form-control" required /></div>
									</div>	
									<div class="form-group">
										<label for="pass" class="control-label col-sm-2"><?php echo $lang['PASSWORD'];?> *</label>
										<div class="col-sm-4"><input id="authpass" type="password" class="form-control" required /></div>
									</div>
									<div class="form-group">
										<label for="pass" class="control-label col-sm-2"><?php echo $lang['REMEMBER'];?></label>
										<div class="col-sm-4"><input id="authremember"  type="checkbox" class="form-control" style="width:auto" /></div>
									</div>									
									<div class="form-group">        
										<div class="col-sm-offset-2 col-sm-10">
											<button type="submit" class="btn btn-default"><i class="fa fa-check"></i> <?php echo $lang['SUBMIT'];?></button>
										</div>
									</div>										

								</form>								

							</div>
						</div>

					</div>					
					
					<div class="tabs" id="tab-createchan">

						<div class="panel panel-primary">
							<div class="panel-heading"><?php echo $lang['CREATE_CHANEL'];?> <a href="#" onclick="gwchat.tabOff();" class="pull-right"><i class="fa fa-times" style="color:white"></i></a></div>
							<div class="panel-body">

								<form class="form-horizontal" role="form" onsubmit="gwchat.createChan();return false">
									<div class="form-group">
										<label for="user" class="control-label col-sm-2"><?php echo $lang['CHANEL_NAME'];?> *</label>
										<div class="col-sm-4"><input id="channame" class="form-control" required /></div>
									</div>	
									<div class="form-group">
										<label for="pass" class="control-label col-sm-2"><?php echo $lang['PASSWORD'];?> <span class="text-muted">(<?php echo $lang['PRIVATE_CHAN_NOTE'];?>)</span></label>
										<div class="col-sm-4"><input id="chanpass"  class="form-control" /></div>
									</div>
									<div class="form-group">        
										<div class="col-sm-offset-2 col-sm-10">
											<button type="submit" class="btn btn-default"><i class="fa fa-check"></i> <?php echo $lang['SUBMIT'];?></button>
										</div>
									</div>										

								</form>								

							</div>
						</div>

					</div>	

					<div class="tabs" id="tab-joinchan">

						<div class="panel panel-primary">
							<div class="panel-heading"><?php echo $lang['JOIN_CHANEL'];?> <a href="#" onclick="gwchat.tabOff();" class="pull-right"><i class="fa fa-times" style="color:white"></i></a> </div>
							<div class="panel-body">

								<form class="form-horizontal" role="form" onsubmit="gwchat.joinChan();return false">
									<div class="form-group">
										<label for="user" class="control-label col-sm-2"><?php echo $lang['CHANEL_NAME'];?> *</label>
										<div class="col-sm-4"><input id="joinname" class="form-control" required /></div>
									</div>	
									<div class="form-group">
										<label for="pass" class="control-label col-sm-2"><?php echo $lang['PASSWORD'];?> <span class="text-muted">(<?php echo $lang['CHAN_PASS_NOTE'];?>)</span></label>
										<div class="col-sm-4"><input id="joinpass"  class="form-control" /></div>
									</div>
									<div class="form-group">        
										<div class="col-sm-offset-2 col-sm-10">
											<button type="submit" class="btn btn-default"><i class="fa fa-check"></i> <?php echo $lang['SUBMIT'];?></button>
										</div>
									</div>										

								</form>								

							</div>
						</div>

					</div>	
					
				</div>

				<table style="margin: 50px;" class="testpannel disconnected">
					<tr></tr>
					<tr>
						<th><?php echo $lang['CONNECT_TO_SERVER'];?></th>
						<td><?php echo $lang['SERVER_ADDR'];?></td>
						<td><input id="serverurl" class="form-control" value="<?php echo $url;?>"></td><td></td><td></td>
						<td>
							<button id="btn-connect" class="btn btn-primary disconnected" onclick="gwchat.connect()"><i class="fa fa-link"></i> <?php echo $lang['CONNECT'];?></button>
						</td>
					</tr>

				</table>	
			</div>
			<div id="chatbottom" class="chatbottom connected">	


				<table id="chatcontainer">
					<tr>
						<td class="channels ">
							
							<ul class="nav nav-pills nav-stacked" id="roomslist">
								
								
							</ul>
							
						</td>
						<td id="" style="height:100%;width:100%">
							
	
								<div id="messagecontent"></div>
		

						</td>
						<td id="userscontent">
						</td>
					<tr>
					<tr><td></td><td>
						<input id="roomInput" >
						<button id="roomInputSend" class="btn" >Send</button>
						</td><td></td></tr>
				</table>






			</div>

		</div>



	</div>
</body>

</html>