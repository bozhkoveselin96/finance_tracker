function addBudget() {
    let selectCategory = $("#category");

    $.get("app/index.php?target=category&action=getAll",
        {
            user_id: sessionStorage.getItem("id"),
            category_type: 0,
        },
        function (data) {
            $.each(data.data, function (key, value) {
                selectCategory.append($("<option />").val(value.id).text(value.name));
            });
        }, 'json')
        .fail(function (xhr, status, error) {
            alert(error);
        });
}

function showUserBudgets() {
    $.get("app/index.php?target=budget&action=getAll",
        {
            user_id: sessionStorage.getItem("id"),
        },
        function (data) {
                let table = $("<table />");
                table.attr("id", "budgets-table");

                table.append("<tr><th>Budgets</th></tr>");

                $.each(data.data, function (key, value) {
                    let tr = $("<tr />");
                    tr.attr("id", value.id);
                    $.each(value, function (k, v) {
                        let td = $("<td />").text(v);


                        td.addClass(k);
                        tr.append(td);
                    });
                    table.append(tr);
                });

                $("#container").append(table);
        }, 'json')
        .fail(function (xhr, status, error) {
            alert(error);
        });
}