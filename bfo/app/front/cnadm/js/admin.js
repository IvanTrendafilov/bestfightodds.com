document.addEventListener("DOMContentLoaded", function(event) { 
    const create_matchup_button = document.getElementById('create-matchup-button');
    if (create_matchup_button != null)
    {
        create_matchup_button.addEventListener('click', function(e) {
            e.preventDefault();
            var opts = {
                method: 'POST',      
                headers: {
                    'Content-type': 'application/json; charset=UTF-8'
                },
                body: JSON.stringify({
                    event_id: document.querySelector('#event-id').value,
                    team1_name: document.querySelector('#team1').value,
                    team2_name: document.querySelector('#team2').value
                })
            };
            fetch('/cnadm/api/matchups', opts).then(function (response) {
                return response.json();
            })
            .then(function (body) {
                if (body.error == true) {
                    alert(body.msg);
                }
                else {
                    window.location.href = '/cnadm/events/' + document.querySelector('#event_id').value;
                }
            });
        });
    }
});