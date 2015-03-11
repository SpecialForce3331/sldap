function getMysqlUsers() //получаем пользователей из БД MySQL
{
    sendAJAXCommand("/api",{action: "getMysqlUsers"}, function(data){

        $("#main").empty();
        $("#main").append("<h3>Пользователи прокси сервера</h3>");
        $("#main").append("<table cellspacing='10' id='users'><thead>" +
            "<tr>" +
            "<td>[]</td>" +
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
            //"<button onclick='showEditUsers()'>Изменить</button>" +
            "<button onclick='doWithUsers(\"deleteUsers\")'>Удалить</button>");
        $("#panel").append("<br>" +
            "<button onclick='appyPatternToUsers()'>Применить шаблон на выбранных пользователей: </button>" +
            "<select class='patterns'>" +
            "</select");
        getPatternsForList();
    }, true);
}

function getLdapUsers(type) //получаем список пользователей из AD
{
    sendAJAXCommand("/api",{action: "getLdapUsers", type: type}, function(data){

        $("#main").empty();
        $("#panel").empty();
        $("#main").append("<h3>Список пользователей из AD</h3><b>Здесь отображаются еще не добавленные пользователи.</b><br>");

        $("#main").append("<table id='ldapUsers'></table>");
        $("#ldapUsers").append("<thead>" +
            "<tr>" +
            "<td>[]</td>" +
            "<td>ФИО</td>" +
            "<td>Логин</td>" +
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

    }, true);
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
			$.post("/api", { action: what, data: checkedUsers }, function(data)
					{
						if ( data.result == "ok" )
                        {
                            $("#main").empty();
                            $("#panel").empty();

                            if ( what == "addUsers")
                            {
                                getLdapUsers('users');
                            }
                            else if ( what == "deleteUsers" )
                            {
                                getMysqlUsers();
                            }
                            else if ( what == "cleanTraffic" )
                            {
                                getMysqlUsers();
                            }
                        }

						alert(data.message);

					}, "json");
		
		}
	else
		{
			alert("вы не выбрали ни одной учетной записи для действий");
		}
	
}

function getPatterns() // получаем шаблоны из БД и отображаем
{
    $("#main").empty();

    sendAJAXCommand("/api",{action: "getPatterns"}, function(data){

        $("#main").append("<table id='patterns'>" +
            "<thead><tr><td>[]</td>" +
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

        $("#panel").empty();
        $("#panel").append("<div>Операции с шаблонами</div>");
        $("#panel").append("" +
            "<button onclick='selectAll()'>Выделить все</button>" +
            "<button onclick='cleanSelectAll()'>Снять выделение</button>" +
            "<button onclick='showFormCreatePattern()'>Создать</button>" +
            "<button onclick='showEditPattern()'>Изменить</button>" +
            "<button onclick='deletePattern()'>Удалить</button>" +
            "");

        applyStyleForTable($("#patterns"));
    }, true);


}

function getPatternsForList() // получаем шаблоны из бд и наполняем выпадающий список при редактировании пользователей этими шаблонами
{
    sendAJAXCommand("/api",{action: "getPatterns"}, function(data)
    {
        for ( var i = 0; i < data.result.length; i++ )
        {
            $(".patterns").each(function(){ $(this).append("<option value='" + data.result[i][3] + "'>" + data.result[i][0] + "</option>")});
        }
    }, true);
}

function getDenySites() // получаем список запрещенных сайтов из бд
{
	$("#main").empty();
	$("#panel").empty();
	
	$("#main").append("<table id='denySites'>" +
			"<thead>" +
                "<tr>" +
                    "<td>[]</td>" +
                    "<td>Адрес сайта</td>" +
                "</tr>" +
            "</thead></table>");
	$("#denySites").append("<tbody>");

    sendAJAXCommand("/api",{action: "getDenySites"}, function(data){
        for ( var i = 0; i < data.result.length; i++ )
        {
            $("#denySites").append("" +
                "<tr>" +
                "<td>" +
                "<input type='checkbox' id=" + data.result[i][0] + " />" +
                "</td>" +
                "<td>" + data.result[i][1] + "</td>" +
                "</tr>");
        }

        $("#denySites").append("</tbody>");
        applyStyleForTable($("#denySites"));

        $("#panel").append("" +
            "<button onclick='selectAll()'>Выбрать все</button>" +
            "<button onclick='cleanSelectAll()'>Снять выбор со всех</button>" +
            "<button onclick='showEditDenySite()'>Изменить</button>" +
            "<button onclick='deleteDenySite()'>Удалить</button>" +
            "<button onclick='showFormCreateDenySite()'>Создать</button>");
    }, true);
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
	$("#patterns").append("<tr><td>[]</td>" +
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
        sendAJAXCommand("/api",{action: "createPattern", name: name, traffic: traffic, access: access}, function(data){
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
        }, true);
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

    sendAJAXCommand("/api",{action: "deletePattern", patterns: checkedPatterns}, getPatterns);

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
            sendAJAXCommand("/api",{action: "applyChangesToUsers", changes: changes}, getMysqlUsers);
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
        sendAJAXCommand("/api",{action: "applyChangesToUsers", changes: changes}, getMysqlUsers);
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
            sendAJAXCommand("/api",{action: "applyChangesToPatterns", changes: changes}, function(){
                getPatterns();
            })
		}
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
    sendAJAXCommand("/api",{action: "createDenySite", url: url}, getDenySites);
}

function showEditDenySite()
{
    var checkedSites = [];

    $("input[type=checkbox]:checked").each(function(){
        checkedSites.push( [ $(this).attr("id"), $(this).parent().next().html() ] );
    });

    $("#main").empty();
    $("#panel").empty();

    $("#main").append("<h3>Адрес блокируемого сайта</h3>");

    checkedSites.forEach( function(site)
    {
        $("#main").append("<div id="+ site[0] +"><input type='text' value='"+ site[1] +"' /></div>");
    });

    $("#panel").append("<button onclick='applyChangesToDenySites()'>Применить</button><button onclick='getDenySites()'>Отмена</button>");
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
    sendAJAXCommand("/api",{action: "deleteDenySite", url: checked}, getDenySites);
}

function applyChangesToDenySites()
{
    var changes = [];

    $("#main > div").each(function(){
       changes.push( [$(this).attr("id"), $(this).find("input").val()] );
    });

    sendAJAXCommand("/api", {action: "editDenySite", changes: changes}, getDenySites );
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

//Передается jquery объект таблицы
function applyStyleForTable(table) {
    table.dataTable( {
        "scrollY":        "450px",
        "scrollCollapse": true,
        "paging":         false,
        "language": {"url": "/static/DataTables-1.10.0/russian.lang"},
        "bRetrieve": true
    } );
}

var maxStatisticRecords = 15;

function showStatistic()
{

    $("#main").empty();
    $("#panel").empty();
    $("#main").append("<div>Укажите дату и выберите тип статистики.</div>" +
        "<div>Если вы не укажите одну из дат, запрос статистики будет осуществлен за сегодняшний день.</div>" +
        "<div><b>ВНИМАНИЕ!</b> Запрос статистики за большой период может вызвать дополнительную нагрузку на сервер, не ставьте большой промежуток без необходимости.</div>");
    $("#main").append("" +
        "Дата с <input id='fromDate' /> по <input id='toDate' />" +
        "<div class='btn-blue' onclick=\"getTopList('login', " + maxStatisticRecords + ", $('#fromDate').val(), $('#toDate').val() )\">Топ 15 пользователей</div>" +
        "<div class='btn-blue' onclick=\"getTopList('site', " + maxStatisticRecords + ", $('#fromDate').val(), $('#toDate').val() )\">Топ 15 сайтов</div>" +
        "<div class='btn-blue' onclick=\"selectUserFromPopupUserList()\">Выбрать пользователя и тип статистики</div>" +
        "<div style='display: none;' id='userList'></div>"
    );

    $.datepicker.formatDate( "dd.mm.yy", new Date());

    $("#fromDate").datepicker();
    $("#toDate").datepicker();


}

function getTopList(type, count, fromDate, toDate, login)
{
    var header = "";

    if ( type === "login" )
    {
        header = "Пользователи";
    }
    else if( type === "site" )
    {
        header = "Сайты"
    }

    $("#main").empty();
    $("#panel").empty();

    $("#main").append("<div id='loading'>Подождите, идет загрузка...</div>");

    sendAJAXCommand("/api",{action: "getTop", type:type, count: count, fromDate: fromDate, toDate: toDate, login: login}, function(data)
    {
        $("#loading").hide();
        $("#main").append("<table id='topStats'></table>");
        $("#topStats").append("" +
            "<thead>" +
            "<tr>" +
            "<td>Траффик в Мбайтах</td>" +
            "<td>"+header+"</td>" +
            "</tr>" +
            "</thead><tbody>");

        for( var i = 0; i < data.data.length; i++ )
        {
            $("#topStats").append("" +
                "<tr>" +
                "<td>" + (data.data[i][0]/1048576).toFixed(3) + "</td>" +
                "<td>"+data.data[i][1]+"</td>" +
                "</tr>"
            );
        }

        $("#topStats").append("</tbody>");
        applyStyleForTable($("#topStats"));
    }, true);
}

function selectUserFromPopupUserList()
{
    sendAJAXCommand("/api", {action: "getMysqlUsers"}, function (data) {
        $("#userList").append("<table id='user_table'>" +
        "<thead><tr>" +
        "<td>ФИО</td>" +
        "<td>Логин</td>" +
        "</tr></thead><tbody>" +
        "</table>");

        for (var i = 0; i < data.result.length; i++) {
            $("#user_table").append(
                "<tr class='selectable-row' onclick=\"selectUserPopup(this, \'" + data.result[i][0] + "\')\">" +
                "<td>" + data.result[i][1] + "</td>" +
                "<td>" + data.result[i][0] + "</td>" +
                "</tr>");
        }

        $("#user_table").append("</tbody>");

        applyStyleForTable($("#user_table"));

        $("#userList").dialog({
            width: 500,
            buttons: [
                {
                    text: "OK",
                    click: function () {
                        var login = $("tr.selectable-row-selected").find("td").last().html();
                        $(this).dialog("destroy");
                        getTopList('site', maxStatisticRecords, $('#fromDate').val(), $('#toDate').val(), login);
                    }
                },
                {
                    text: "Отмена",
                    click: function () {
                        $(this).dialog("destroy");
                    }
                }
            ]
        });

    }, true);
}

function selectUserPopup(object)
{
    $(object).parent().find("tr").each(function()
    {
        if ( $(this).hasClass('selectable-row-selected') )
        {
            toggleCssClass($(this), 'selectable-row', 'selectable-row-selected');
        }
    });

    toggleCssClass($(object), 'selectable-row', 'selectable-row-selected');
}

function selectAdminsFromPopupList()
{
    sendAJAXCommand("/api", {action: "getLdapUsers", type: "admins"}, function (data) {
        $("#adminList").html("<table id='admin_table'>" +
        "<thead><tr>" +
        "<td>ФИО</td>" +
        "<td>Логин</td>" +
        "<td>Шаблон прав</td>" +
        "</tr></thead><tbody>" +
        "</table>");

        for (var i = 0; i < data.result.count; i++) {
            var sam = data.result[i]['samaccountname'][0];
            var name = data.result[i]['dn'].split(",")[0].split("CN=")[1];
            $("#admin_table").append(
                "<tr class='selectable-row'>" +
                "<td>" + name + "</td>" +
                "<td>" + sam + "</td>" +
                "<td>" +
                    "<select class='permissions'></select>" +
                "</td>" +
                "</tr>");
        }

        getPermissionList();

        $("#admin_table").append("</tbody>");

        $("#admin_table tr").each(function(){
            $(this).click(function(){
                toggleCssClass($(this), 'selectable-row', 'selectable-row-selected');
            });
        });

        applyStyleForTable($("#admin_table"));

        $("#adminList").dialog({
            width: 500,
            buttons: [
                {
                    text: "OK",
                    click: function ()
                    {
                        var admins = new Array();

                        $("tr.selectable-row-selected").each(function()
                        {
                            admins.push([$(this).find("td")[1].innerHTML, $(this).find("td select").val()]);
                        });

                        createAdminAccount(admins);

                        $(this).dialog("destroy");
                    }
                },
                {
                    text: "Отмена",
                    click: function () {
                        $(this).dialog("destroy");
                    }
                }
            ]
        });

    }, true);
}

function showPreferences()
{
    $("#main").empty();
    $("#panel").empty();

    $("#main").append("<div class='btn-blue' onclick='showAdmins()'>Управление учетными записями администраторов</div>");
    $("#main").append("<div class='btn-blue' onclick='showPermissionPatterns()'>Управление шаблонами прав</div>");
}

function showAdmins()
{
    $("#main").empty();
    $("#panel").empty();

    sendAJAXCommand("/api",{action: "getAdmins"}, function(data){
        $("#main").append("<table id='admins'></table>");
        $("#admins").append("" +
            "<thead>" +
            "<tr>" +
            "<td>[]</td>" +
            "<td>Логин</td>" +
            "<td>Набор Прав</td>" +
            "</tr>" +
            "</thead><tbody>");

        for( var i = 0; i < data.data.length; i++ )
        {
            $("#admins").append("" +
                "<tr>" +
                "<td><input id="+ data.data[i][0] +" type='checkbox'/></td>" +
                "<td>" + data.data[i][1] + "</td>" +
                "<td id="+ data.data[i][3] +">"+ data.data[i][2] + "</td>" +
                "</tr>"
            );
        }

        $("#admins").append("</tbody>");
        applyStyleForTable( $("#admins") );
    }, true);

    $("#panel").append("" +
    "<div style='display: none;' id='adminList'></div>" +
    "<button onclick='showFormCreateAdmin()'>Создать</button>" +
    "<button onclick='selectAdminsFromPopupList()'>Добавить</button>" +
    "<button onclick='doWithAdmins(\"edit\")'>Изменить</button>" +
    "<button onclick='doWithAdmins(\"delete\")'>Удалить</button>");

}

function doWithAdmins( what )
{
    var checkedAdmins = new Array();

    $("input[type=checkbox]:checked").each( function( index, input ){

        var id = $(input).attr('id');
        var login = $(input).parent().parent().children()[1].innerHTML;
        var permission_id = $(input).parent().parent().children()[2].id;

        checkedAdmins.push([id, login, permission_id]);
    });
    if ( what == "edit" )
    {
        showEditAdmins( checkedAdmins );
    }
    else if( what == "delete" )
    {
        deleteAdmins(checkedAdmins);
    }
}

function showFormCreateAdmin()
{
    $("#main").empty();
    $("#panel").empty();

    $("#main").append("<h4>Создание учетной записи администратора</h4></br>");

    $("#main").append("<input id='login' type='text' placeholder='Логин' /></br>");
    $("#main").append("<input id='password' type='password' placeholder='Пароль' /></br>");
    $("#main").append("<input id='retype_password' type='password' placeholder='Подтверждение пароля' /></br>");
    $("#main").append("<select class='permissions'></select></br>");
    $("#main").append("<button onclick='createAdminAccount()'>Создать</button></br>");

    getPermissionList();
}

function showEditAdmins( checkedAdmins )
{
    $("#main").empty();
    $("#panel").empty();

    $("#main").append("<table id='editAdmins'></table>");
    $("#editAdmins").append("<thead>" +
        "<tr>" +
        "<td>Логин</td>" +
        "<td>Пароль</td>" +
        "<td>Повтор пароля</td>" +
        "<td>Шаблон разрешений</td>" +
        "</tr></thead><tbody>");

    checkedAdmins.forEach( function( admin )
    {
        $("#editAdmins").append("" +
            "<tr>" +
            "<td><input id="+admin[0]+" type='text' value="+admin[1]+" /></td>" +
            "<td><input type='password'/></td>" +
            "<td><input type='password'/></td>" +
            "<td><select class='permissions'></select></td>" +
            "</tr>");
    });

    $("#editAdmins").append("</tbody>");
    getPermissionList();

    $("#panel").append("<button onclick='applyChangesToAdmin()'>Применить</button><button onclick='showAdmins()'>Отмена</button>");
}

function getPermissionList()
{
    sendAJAXCommand("/api",{action: "getPermissions"}, function(data){
        for( var i = 0; i < data.data.length; i++ )
        {
            $(".permissions").append("<option value=" + data.data[i][0] + ">" + data.data[i][1] + "</option>");
        }
    }, true);
}

function createAdminAccount(data)
{
    if( typeof data != 'undefined' )
    {
        sendAJAXCommand("/api", {
            action: "createLdapAdminAccounts",
            data: data
        }, showAdmins);
    }
    else
    {
        sendAJAXCommand("/api", {
            action: "createAdminAccount",
            login: $("#login").val(),
            password: $("#password").val(),
            retype_password: $("#retype_password").val(),
            permission_id: $(".permissions").val()
        }, showAdmins);
    }

}

function deleteAdmins(data)
{
    sendAJAXCommand("/api", {
        action: "deleteAdmins",
        data: data
    }, showAdmins);
}

function applyChangesToAdmin()
{
    var changes = new Array();

    for( var i = 1; i < $("tr").length; i++ )
    {
        var id = $($("tr").get(i)).find("input:first").attr("id");
        var login = $($("tr").get(i)).find("input:first").val();
        var password = $($("tr").get(i)).find("input").get(1).value;
        var retype_password = $($("tr").get(i)).find("input").get(2).value;
        var permission_id = $("select.permissions").val();

        changes.push([id,login, password, retype_password, permission_id]);
    }
    if ( changes.length > 0 )
    {
        sendAJAXCommand("/api",{action: "applyChangesToAdmin", changes: changes}, showAdmins )
    }
}

function showPermissionPatterns()
{
    $("#main").empty();
    $("#panel").empty();

    $("#main").append("<button onclick='$(\"#newPattern\").show(); $(\".permissions\").hide();'>Новый шаблон прав</button>" +
        "<div style='display: none;' id='newPattern'><input id='patternName' type='text' placeholder='Имя шаблона' /><button onclick='showPermissionPatterns()'>Отмена</button></div>");

    $("#main").append("<select onchange='getPermissionsById($(this).val())' class='permissions'></select>");
    $(".permissions").append("<option disabled selected>Выберите шаблон</option>");

    $("#main").append("<ul id='permissionList'></ul>");

    $("#permissionList").append("<li><label>Пользователи</label>" +
        "<ul>" +
            "<li><label><input id='addUsers' type='checkbox'/>Добавление пользователей</label></li>" +
            "<li><label><input id='editUsers' type='checkbox'/>Редактирование пользователей</label></li>" +
            "<li><label><input id='deleteUsers' type='checkbox'/>Удаление пользователей</label></li>" +
        "</ul>" +
    "</li>");

    $("#permissionList").append("<li><label>Шаблоны траффика</label>" +
        "<ul>" +
            "<li><label><input id='createPatterns' type='checkbox'/>Добавление шаблонов</label></li>" +
            "<li><label><input id='editPatterns' type='checkbox'/>Редактирование шаблонов</label></li>" +
            "<li><label><input id='deletePatterns' type='checkbox'/>Удаление шаблонов</label></li>" +
        "</ul>" +
    "</li>");

    $("#permissionList").append("<li><label>Запрещенные сайты</label>" +
        "<ul>" +
            "<li><label><input id='addDenySites' type='checkbox'/>Добавление сайтов</label></li>" +
            "<li><label><input id='editDenySites' type='checkbox'/>Редактирование сайтов</label></li>" +
            "<li><label><input id='deleteDenySites' type='checkbox'/>Удаление сайтов</label></li>" +
        "</ul>" +
    "</li>");

    $("#permissionList").append("<li><label>Администраторы</label>" +
        "<ul>" +
            "<li><label><input id='createAdmins' type='checkbox'/>Добавление администраторов</label></li>" +
            "<li><label><input id='editAdmins' type='checkbox'/>Редактирование администраторов</label></li>" +
            "<li><label><input id='deleteAdmins' type='checkbox'/>Удаление администраторов</label></li>" +
        "</ul>" +
    "</li>");

    $("#permissionList").append("<li><label>Шаблоны прав доступа</label>" +
        "<ul>" +
            "<li><label><input id='createPermissions' type='checkbox'/>Добавление шаблона прав</label></li>" +
            "<li><label><input id='editPermissions' type='checkbox'/>Редактирование шаблона прав</label></li>" +
            "<li><label><input id='deletePermissions' type='checkbox'/>Удаление шаблона прав</label></li>" +
        "</ul>" +
    "</li>");


    getPermissionList();

    $("#panel").append("<button onclick='applyChangesToPermissions()'>Применить изменения</button>")

}

function getPermissionsById(id)
{
    $("input[type=checkbox]:checked").each(function(index, checkbox){ $(checkbox).prop("checked",false)});

    sendAJAXCommand("/api",{action: "getPermissionsById", id: id }, function(data){

        var data = data.data;

        for ( var key in data)
        {
            if( data[key] )
            {
                $("#" + key).prop("checked", true);
            }
        }
    }, true);
}

function applyChangesToPermissions()
{
    var id = $(".permissions:visible").val();
    id = id == undefined ? "" : id;

    var permissions = new Array();
    var name = $("#patternName").val();

    $("input[type=checkbox]").each( function(index, row){
        permissions.push( [$(row).attr("id"), $(row).prop("checked")] );
    });

    sendAJAXCommand("/api",{action: "applyChangesToPermissions", id: id, name: name, permissions: permissions}, showPermissionPatterns );
}

function sendAJAXCommand(url, params, callbackFunction, needData)
{
    $("#loading-container").show();

    $.post(url, params, function( data )
    {
        if ( data.message && data.message.length > 0 )
        {
            alert( data.message );
        }

        if( data.result != "error" )
        {
            if( needData )
            {
                callbackFunction(data);
            }
            else
            {
                callbackFunction();
            }

        }
        $("#loading-container").hide();
    },"json");
}

//Функция заменяет существующий класс новым, порядок передачи классов значения не имеет.
function toggleCssClass(object, class1, class2)
{
    toAddClass = $(object).hasClass(class1) ? class2 : class1;
    toRemoveClass = toAddClass === class1 ? class2 : class1;

    $(object).removeClass(toRemoveClass);
    $(object).addClass(toAddClass);
}