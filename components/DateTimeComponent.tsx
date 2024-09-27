import React, { useState, useEffect } from 'react';

const DateTimeComponent: React.FC = () => {
    const [date, setDate] = useState('');
    const [time, setTime] = useState('');

    useEffect(() => {
        function updateTime() {
            const options: Intl.DateTimeFormatOptions = {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                timeZone: 'America/Sao_Paulo',
                hour12: false,
            };

            const timeOptions: Intl.DateTimeFormatOptions = {
                timeZone: 'America/Sao_Paulo',
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            };

            const now = new Date();
            const dateString = now.toLocaleDateString('en-US', options);
            const timeString = now.toLocaleTimeString('en-US', timeOptions);

            setDate(dateString);
            setTime(timeString);
        }

        updateTime();
        const interval = setInterval(updateTime, 1000);
        return () => clearInterval(interval);
    }, []);

    return (
        <div>
            <style jsx>{`
            #date {
            font-size: 40px;
            }
            #time {
            font-size: 40px;
            }
            .city {
            font-size: 40px;
            }
        `}</style>
            <p><span id="date">{date}</span></p>
            <p><span className="city">São Paulo: </span><span id="time">{time}</span></p>
        </div>
    );
};

export default DateTimeComponent;
