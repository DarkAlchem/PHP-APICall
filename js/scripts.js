//Worked on a Fallback incase the all PHP approach didn't work

fetch('https://quotes.rest/qod.json')
    .then(function(response){
        console.log('Success',response);
    }).catch(function (err){
        console.log('Failure',err);
    });