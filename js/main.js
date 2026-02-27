function countdown( elementName )
{
    let elements = [];
    let date = new Date();
    let day = date.getDate();
    let month = date.getMonth();
    let year = date.getFullYear();
    let endTime = new Date(year,month,day,23,59,59);

    function twoDigits( n )
    {
        return (n <= 9 ? "0" + n : n);
    }

    function updateTimer()
    {
        const msLeft = endTime - (+new Date);
        if ( msLeft < 1000 ) {
            Array.from(elements).forEach(function(element) {
                element.innerHTML = "countdown's over!";
            });
        } else {
            const time = new Date( msLeft );

            let seconds = Math.floor(msLeft / 1000);
            let minutes = Math.floor(seconds / 60);
            let hours = Math.floor(minutes / 60);

            seconds = seconds % 60;
            minutes = (seconds >= 30 ? minutes + 1 : minutes) % 60;

            Array.from(elements).forEach(function(element) {
                element.innerHTML = ((hours ? hours + ':' + twoDigits( minutes ) : minutes) + ':' + twoDigits( seconds ));
            });
            setTimeout( updateTimer, time.getUTCMilliseconds() + 500 );
        }
    }

    elements = document.getElementsByClassName( elementName );
    updateTimer();
}

countdown( "app-timer" );
