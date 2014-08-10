function getMysqlUsers() //получаем пользователей из БД MySQL
{
	$.post("mysql.php", { action: "getMysqlUsers" }, function(data)
			{	
				$("#main").empty();
				$("#main").append("<h3>Пользователи прокси сервера</h3>");
				$("#main").append("<table cellspacing='10' id='users'><thead>" +
						"<tr>" +
						"<td></td>" +
						"<td>ФИО Пользователя</td>" +
						"<td>Логин</td>" +
						"<td>Потребленный траффик (Мбайт)</td>" +
						"<td>Разрешенный траффик (Мбайт)</td>" +
						"<td>Примененный шаблон</td>" +
						"<td>Доступ к запрещенным сайтам</td>" +
						"</tr>" +
						"</thead><tbody></table>");
				
				var access;
				var allowTraffic;
				
				for ( var i = 0; i < data.result.length; i++ ) //парсим ответ
					{
					
						if ( data.result[i][5] != 0 ) //вместо 0 и 1 выводим словами
							{
								access = "Есть";
							}
						else
							{
								access = "Нет";
							}
						if ( data.result[i][3] == 0 ) //если траффик у пользователя  0  = безлимит
							{
								allowTraffic = "безлимит"
							}
						else
							{
								allowTraffic = data.result[i][3];
							}

						$("#users").append("<tr>" +
								"<span>" +
								"<td><input type='checkbox'/></td>" +
								"<td width='40%'>" + data.result[i][1] + " </td>" + 	//ФИО
								"<td>" + data.result[i][0] + "</td>" + 					//Логин
								"<td>" + data.result[i][2] + "</td>" + 					//Потребленный траффик
								"<td>" + allowTraffic + "</td>" +						//Разрешенный траффик
								"<td>" + data.result[i][4] + "</td>" +					//Примененный шаблон
								"<td>" + access + "</td>" +								//Доступ к запрещенным сайтам
								"</span></tr>");
					}
                $("#users").append("</tbody>");
                applyStyleForTable($("#users"));
				
				$("#panel").empty();
				$("#panel").append("" +
						"<button onclick='selectAll()'>Выбрать всех</button>" +
						"<button onclick='cleanSelectAll()'>Снять выбор со всех</button>");
				$("#panel").append("" +
						"<button onclick='doWithUsers(\"cleanTraffic\")'>Обнулить траффик</button>" +
						"<button onclick='showEditUsers()'>Изменить</button>" +
						"<button onclick='doWithUsers(\"deleteUsers\")'>Удалить</button>");	
				$("#panel").append("<br>" +
						"<button onclick='appyPatternToUsers()'>Применить шаблон на выбранных пользователей: </button>" +
						"<select class='patterns'>" +
						"</select");
					getPatternsForList();
			}, "json");
}

function getLdapUsers() //получаем список пользователей из AD
{
	$.post("mysql.php", { action: "getMysqlUsers" }, function(data)
			{
				var existUsers = data.result;
				
				$.post("ldap.php", { action: "getLdapUsers", existUsers: existUsers }, function(data)
						{
							$("#main").empty();
                            $("#panel").empty();
							$("#main").append("<h3>Список пользователей из AD</h3><b>Здесь отображаются еще не добавленные пользователи.</b><br>");

                            $("#main").append("<table id='ldapUsers'></table>");
                            $("#ldapUsers").append("<thead>" +
                                "<tr>" +
                                "<td></td>" +
                                "<td>Логин</td>" +
                                "<td>ФИО</td>" +
                                "<td>Шаблон</td>" +
                                "</tr></thead><tbody>");

                            for ( var i = 0; i < data.result.count; i++ )
								{
									var name = data.result[i]["dn"];
									name = name.split(",")
									name = name[0].substr(3);
									
									if (!data.result[i]["samaccountname"]){continue}
									var sam = data.result[i]["samaccountname"][0];

									$("#ldapUsers").append("<tr>" +
											"<td><input type='checkbox'/></td>" +
											"<td>" + name + "</td>" +
											"<td>" + sam + "</td>" +
                                            "<td><select class='patterns'></select></td>" +
											"</tr>");

								}
                            $("#ldapUsers").append("</tbody>");
                            getPatternsForList();
                            applyStyleForTable($("#ldapUsers"));

							$("#panel").append("<button onclick='selectAll()'>Выбрать всех</button><button onclick='cleanSelectAll()'>Снять выбор со всех</button><button onclick='doWithUsers(\"addUsers\")'>Добавить</button>");
						}, "json");
			}, "json");
}

function doWithUsers(what) //работа с пользователями в БД Mysql
{
	var checkedUsers = new Array();
	
	for ( var i = 0; i < $("input[type='checkbox']").length; i++ ) //выбираем отмеченных пользователей
		{
			if ( $("input[type='checkbox']")[i].checked == true )
				{
					checkedUsers.push( $("input[type='checkbox']")[i].parentNode.parentNode.children[1].innerHTML);
					checkedUsers.push( $("input[type='checkbox']")[i].parentNode.parentNode.children[2].innerHTML);
                    checkedUsers.push( $(".patterns").val().split(",")[0] );
				}
		}
	
	if ( checkedUsers.length > 0 )
		{
			$.post("mysql.php", { action: what, data: checkedUsers }, function(data)
					{
						$("#main").empty();
						
						if ( what == "addUsers") 
						{
					        alert("Пользователи успешно добавлены.");
                            getLdapUsers();
						}
						else if ( what == "deleteUsers" ) 
						{
                            alert("Пользователи успешно удалены.");
                            getMysqlUsers();
						}
						else if ( what == "cleanTraffic" )
						{
                            alert("Траффик выбранных пользователей успещно очищен.");
                            getMysqlUsers();
						}
						else 
						{
                            alert("Некорректный запрос.");
                            getMysqlUsers();
						}
						
						$("#panel").empty();
					}, "json");
		
		}
	else
		{
			alert("вы не выбрали ни одной учетной записи для действий");
		}
	
}

function getPatterns() // получаем шаблоны из БД и отображаем
{
	$.post("mysql.php", { action: "getPatterns" }, function(data)
			{
				$("#main").empty();
				$("#main").append("<table id='patterns'>" +
						"<thead><tr><td></td>" +
						"<td>Название шаблона</td>" +
						"<td>Объем траффика в Мбайт</td>" +
						"<td>Доступ к запрещенным сайтам</td>" +
						"</tr></thead>" +
						"<tbody></table>");
				
				var access;//доступ к запрещенным сайтам аля вконтакте
				
				for ( var i = 0; i < data.result.length; i++ )
				{
					if ( data.result[i][2] != "0" )
					{
						access = "есть";
					}
					else
					{
						access = "нет";
					}
					
					$("#patterns").append("<tr><td><input type='checkbox'/></td><td>" + data.result[i][0] + "</td><td>" + data.result[i][1] + "</td><td>" + access + "</td></tr>" );
				}
                $("#patterns").append("</tbody>");
                applyStyleForTable($("#patterns"));
							
				$("#panel").empty();
				$("#panel").append("<div>Операции с шаблонами</div>");
				$("#panel").append("" +
						"<button onclick='selectAll()'>Выделить все</button>" +
						"<button onclick='cleanSelectAll()'>Снять выделение</button>" +
						"<button onclick='showFormCreatePattern()'>Создать</button>" +
						"<button onclick='showEditPattern()'>Изменить</button>" +
						"<button onclick='deletePattern()'>Удалить</button>" +
						"");
			}, "json");

}

function getPatternsForList() // получаем шаблоны из бд и наполняем выпадающий список при редактировании пользователей этими шаблонами
{
	$.post("mysql.php", { action: "getPatterns" }, function(data)
			{ 	
				for ( var i = 0; i < data.result.length; i++ )
				{
					$(".patterns").each(function(){ $(this).append("<option value='" + data.result[i][3] + "'>" + data.result[i][0] + "</option>")});
				}		
			}, "json");
}

function getDenySites() // получаем список запрещенных сайтов из бд
{
	$("#main").empty();
	$("#panel").empty();
	
	$("#main").append("<table id='denySites'>" +
			"<thead>" +
                "<tr>" +
                    "<td></td>" +
                    "<td>Адрес сайта</td>" +
                "</tr>" +
            "</thead><tbody>");
	
	$.post("mysql.php", { action: "getDenySites" }, function(data)
			{ 	
				for ( var i = 0; i < data.result.length; i++ )
				{
					$("#denySites").append("" +
                        "<tr>" +
							"<td>" +
							    "<input type='checkbox' />" +
							"</td>" +
							"<td>" + data.result[i] + "</td>" +
                        "</tr>");
				}

				$("#denySites").append("</tbody></table>");
               applyStyleForTable($("#denySites"));

				$("#panel").append("" +
						"<button onclick='selectAll()'>Выбрать все</button>" +
						"<button onclick='cleanSelectAll()'>Снять выбор со всех</button>" +
						"<button>Изменить</button>" +
						"<button onclick='deleteDenySite()'>Удалить</button>" +
						"<button onclick='showFormCreateDenySite()'>Создать</button>");
				
			}, "json");
}

function tryPatternToUser() //при клике по шаблону из выпадающего списка заполняем поля пользователя данными шаблона
{
	var selectedPattern = $('.patterns');
    var selectedValue = $( 'option:selected', selectedPattern ).val();
    var selected = $( 'option:selected', selectedPattern );
    selectedValue = selectedValue.split(",");
    

    selected = selected.parent()[0].parentNode.parentNode.parentNode;
	
	objectName = selected.children[3].children[0];
	objectTraffic = selected.children[2].children[0];
	objectAccess = selected.children[4].children[0];
	
	objectName.value = selectedValue[0];
	objectTraffic.value = selectedValue[1];
	objectAccess.value = selectedValue[2];	
}

function showFormCreatePattern() // отображаем форму для создания шаблона
{
	$("#patterns").append("<tr><td></td>" +
			"<td><input id='name' type='text' placeholder='Название шаблона'/></td>" +
			"<td><input id='allowTraffic' type='text' placeholder='Кол-во траффика'/></td>" +
			"<td>" +
			"<select id='access'>" +
			"<option value='0'>Запретить</option>" +
			"<option value='1'>Разрешить</option>" +
			"</select>" +
			"<button onclick='createPattern()'>Применить</button>" +
			"</td>" +
			"</tr>");
}

function createPattern() //запрос на сервер с целью создания шаблона
{	
	var name = $("#name").val();
	var traffic = $("#allowTraffic").val();
	var access = $("#access").val();
	
	if ( name != "" && traffic != "" && access != "" )
	{
		$.post("mysql.php", { action: "createPattern", name: name, traffic: traffic, access: access }, function(data)
				{
					if( data.result == "ok" )
					{
						getPatterns();
					}
					else
					{
						$("#main").empty();
						$("#panel").empty();
						
						$("#main").append("<div>Вы не заполнили или несколько полей.</div>" + data.name + " " + data.traffic + " " + data.access);
					}
				
				}, "json");
	}
	else
	{
		$("#main").empty();
		$("#panel").empty();
		
		$("#main").append("<div>Вы не заполнили одно или несколько полей.</div>");
	}
}

function deletePattern() //запрос на сервер с целью удаления шаблона
{
	var checkedPatterns = new Array();
	
	for ( var i = 0; i < $("input").length; i++ )
		{
			if( $("input")[i].checked == true )
				{
					checkedPatterns.push( $("input")[i].parentNode.parentNode.children[1].innerHTML );
				}
		}
	$.post("mysql.php", { action: "deletePattern", patterns: checkedPatterns }, function(data){ getPatterns() }, "json");

}

function showEditUsers() //отображаем форму для редактирования атрибутов пользователей
{
	var checkedUsers = new Array();
	
	for ( var i = 0; i < $("input").length; i++ ) //выбираем отмеченных пользователей
		{
			if ( $("input")[i].checked == true )
				{
					checkedUsers.push( new Array( $("input")[i].parentNode.parentNode.children[1].innerHTML, 
													$("input")[i].parentNode.parentNode.children[2].innerHTML, 
													$("input")[i].parentNode.parentNode.children[4].innerHTML, 
													$("input")[i].parentNode.parentNode.children[5].innerHTML, 
													$("input")[i].parentNode.parentNode.children[6].innerHTML ) );
				}
		}
	
	if ( checkedUsers.length > 0 )
		{
			$("#main").empty();
			$("#panel").empty();
			
			$("#main").append("<table id='editUsers'></table>");
			$("#editUsers").append("" +
					"<thead><tr>" +
					"<td>ФИО</td>" +
					"<td>Логин</td>" +
					"<td>Разрешенный траффик</td>" +
					"<td>Шаблон</td>" +
					"<td>Доступ на запрещенные сайты</td></thead><tbody>");
			
			
			for ( var i = 0; i < checkedUsers.length; i++ )
				{
					$("#editUsers").append("<tr>" +
							"<td><span>" + checkedUsers[i][0] + "</span></td>" +
							"<td><span>" + checkedUsers[i][1] + "</span></td>" +
							"<td><input type='text' value='" + checkedUsers[i][2] + "'></input></td>" +
							"<td><input type='text' value='" + checkedUsers[i][3] + "' readonly ></input><div><select class='patterns' onchange='tryPatternToUser()'></select></div></td>" +
							"<td><input id='access"+[i]+"' type='text' value='" + checkedUsers[i][4] + "' readonly ></input><br>" +
									"<input type='radio' name='access"+[i]+"' onclick='changeAccess(\"#access"+[i]+"\",\"Да\");' />Да" +
									"<input type='radio' name='access"+[i]+"' onclick='changeAccess(\"#access"+[i]+"\",\"Нет\");' />Нет" +
									"</td>" +
							"</tr>");
				}
            $("#editUsers").append("</tbody></table>");
            applyStyleForTable($("#editUsers"));
			$("#panel").append("" +
					"<button onclick='applyChangesToUsers()'>Применить изменения</button>" +
					"<button onclick='getMysqlUsers()'>Отмена</button>");
			
			getPatternsForList(); //получаем шаблоны и наполняем ими выпадающий список
		}
}

function changeAccess( id, access )
{
	$(id).val(access);
}

function showEditPattern() //отображает форму для редактирования шаблонов
{
	var checkedPattern = new Array();
	
	for( var i = 0; i < $("input[type='checkbox']").length; i++ )
		{
			if ( $("input[type='checkbox']")[i].checked == true )
				{
					checkedPattern.push( new Array( $("tbody tr")[i].children[1].innerHTML, $("tbody tr")[i].children[2].innerHTML, $("tbody tr")[i].children[3].innerHTML ) );
				}
		}
	
	if ( checkedPattern.length > 0 )
		{
			$("#main").empty();
			$("#panel").empty();
			
			$("#main").append("<table id='editPattern'></table>");
			
			$("#editPattern").append("<tr><td>Название шаблона</td><td>Объем траффика в Мбайт</td><td>Доступ к запрещенным сайтам</td></tr>")
			
			var currentPatternNames = new Array();
			
			for ( var i = 0; i < checkedPattern.length; i++ )
				{
					currentPatternNames.push( checkedPattern[i][0] );
					
					$("#editPattern").append("<tr>" +
						"<td><input type='text' value=\"" + checkedPattern[i][0] + "\" /></td>" +
						"<td><input type='text' value=\"" + checkedPattern[i][1] + "\" /></td>" +
						"<td>" + checkedPattern[i][2] + "</td>" +
						"<td><select>" +
							"<option value='1'>Есть</option>" +
							"<option value='0'>Нет</option>" +
						"</select></td>" +
					"</tr>");
				}

			$("#panel").append("<tr>" +
					"<td><button onclick='applyChangesToPatterns(\"" + currentPatternNames + "\")'>Применить изменения</button></td>" +
					"<td><button onclick='getPatterns()'>Отмена</button></td>" +
					"</tr>");
			
		}
}

function applyChangesToUsers() //применение изменений пользователей
{
	var changes = new Array();
	var access;
	
	for ( var i = 1; i < $("tr").length; i++ )
		{
			if ( $("tr")[i].children[4].children[0].value == "Нет" )
				{
					access = 0;
				}
			else
				{
					access = 1;
				}
			
			changes.push( new Array( $("tr")[i].children[1].children[0].innerHTML, $("tr")[i].children[2].children[0].value, $("tr")[i].children[3].children[0].value, access ) );
		}
	if ( changes.length > 0 )
		{
			$.post("mysql.php", { action: "applyChangesToUsers", changes: changes }, function(data){ getMysqlUsers() }, "json");
		}
}

function appyPatternToUsers() //применение шаблона сразу на множество пользователей
{
	var changes = new Array();
	var pattern = $("option:selected", $(".patterns")).val();
	var pattern = pattern.split(",");
	
	for ( var i = 1; i < $("tr").length; i++ )
		{
			if ( $("tr")[i].children[0].children[0].checked == true )
				{
					changes.push( new Array( $("tr")[i].children[2].innerHTML, pattern[1], pattern[0], pattern[2] ) );
				}
		}
	if ( changes.length > 0 )
	{
		$.post("mysql.php", { action: "applyChangesToUsers", changes: changes }, function(data){ getMysqlUsers() }, "json");
	}
}

function applyChangesToPatterns( currentPatternNames )
{
	var changes = new Array();
	
	currentPatternNames = currentPatternNames.split(","); //Ебаный, убогий язык не понимает, что на вход передается массив и принимает его как строку.
	
	for ( var i = 1; i < ( $("tr").length -1 ); i++ )
		{
			changes.push( new Array( currentPatternNames[i-1], $("tr")[i].children[0].children[0].value, $("tr")[i].children[1].children[0].value, $("select")[i-1].value ));
		}
	if ( changes.length > 0 )
		{
			$.post("mysql.php",
                { action: "applyChangesToPatterns", changes: changes },
                function(data)
                {
                    if(data.result == "ok")
                    {
                        applyChangesPatternToMysqlUsers(currentPatternNames);
                        getPatterns();

                    } }, "json");
		}
}

function applyChangesPatternToMysqlUsers(currentPatternNames)
{
    $.post("mysql.php",
        {
            action: "updateUsersByPattern",
            patternNames: currentPatternNames
        }, function(data)
        {
            alert("Все пользователи данного шаблона были успешно обновлены.")
        }, "json");
}

function showFormCreateDenySite()
{
	$("#main").empty();
	$("#panel").empty();
	
	var form = "<tr><td><input type=\\\"text\\\" placeholder=\\\"Адрес сайта\\\" /></td><tr>";

	$("#main").append("<i>Внимание! Для проверки используется регулярное выражение, следовательно, чем короче вы укажете адрес сайта, тем вероятнее больше сайтов будет залокированно</i>");
	$("#main").append("<table>" +
			"<tr><td><input type=\"text\" placeholder=\"Адрес сайта\" /></td><tr>" +
			"</table>");
	$("#panel").append("" +
			"<button onclick='$(\"table\").append(\"" + form + "\")'>Добавить поле</button><br>" +
					"<button onclick='getDenySites()'>Отмена</button>" +
					"<button onclick='createDenySite()'>Применить</button>");		
}

function createDenySite()
{
	var url = new Array();
	
	for ( var i = 0; i < $("input").length; i++ )
	{
		url.push( $("input")[i].value );
	}
	
	$.post("mysql.php", { action: "createDenySite", url: url }, function(data){ getDenySites() }, "json");
}

function deleteDenySite()
{
	var checked = new Array();
	
	for ( var i = 0; i < $("input").length; i++ )
	{
		if ( $("input")[i].checked == true )
		{
			checked.push( $("input")[i].parentNode.parentNode.children[1].innerHTML );
		}
	}
	$.post("mysql.php", { action: "deleteDenySite", url: checked }, function(data){ getDenySites() }, "json");
}

function selectAll()
	{
		for ( var i = 0; i < $("input").length; i++ )
			{
				$("input")[i].checked = true;
			}
	}

function cleanSelectAll()
	{
		for ( var i = 0; i < $("input").length; i++ )
			{
				$("input")[i].checked = false;
			}
	}

function applyStyleForTable(table) {
    table.dataTable( {
        "jQueryUI": true
    } );
}
