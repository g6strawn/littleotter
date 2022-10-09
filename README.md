# Little Otter Take Home

[gstrawn.dev/otter](https://gstrawn.dev/otter) - Visualizing bicycle trips.

## Getting Started

### Deployment

* The GitHub code is NOT deployable. For the sake of easy development, I reused some of my existing, proprietary code and therefore deployed on my existing, private server: [gstrawn.dev/otter](https://gstrawn.dev/otter)

### Source code

* All the relevant code is available on GitHub.
- index.php - HTML
- index.css - CSS
- api.php   - back-end
- index.js  - front-end

### Executing program

* [gstrawn.dev/otter](https://gstrawn.dev/otter)

* For the sake of speedy development, this example is not fully responsive. A screen wider than 540px is recommended.

## Development notes

* Fast and easy development was paramount. Every company out there has a "quick take home test" and I'm on vacation so I took every shortcut I could while still trying to create a complete solution.

* First principles - For such a simple assignment I saw no need for React, Charts.js, or any other libraries. Like, how many code monkeys does it take to change a lightbulb? In this particular case: 1.

* permalinks - When I'm interviewing coders, I want to see an understanding of the fundamentals such as fetch(). Therefore I started this solution using fetch. However, it made more sense to keep the API server-side and has the added bonus of permalinks. The GET params are right there in the URL.

## Part 3 Extras

* statistics - average use, busiest surge, busiest time of day

* inventory - The maintenance team has to repair the bikes no matter what. The real issue is inventory. Are some stations overflowing and others empty? Where are the bikes every day.

## Further TODO

* responsive - This shouldn't be too difficult. The only real constraint is the size of the table and underlying canvas. For smaller screens the table may require a smaller font and the canvas will need to resize appropriately.

* artsy - Dammit Jim, I'm a coder, not an artist. A graphic designer would surely have ideas for flashier graphics but I was more focused on easy access to the actual data. Still, a little flash and bling might be nice.

* age / gender - Part 2, if the marketing team "is interested in creating relevant advertising" then they probably also care about gender in addition to age.

* ride duration - Do some stations get long rentals while others are always short trips?

* lat/long - Maps are always fun and the Google Maps API is easy to use. But frankly, right now I'd rather go have another Mai Tai on the beach.

## Author

Gary Strawn - g6strawn@gmail.com

## Version History

* 1.0
    * Initial Release

## License

Copyright (c) 2022 Gary Strawn, All rights reserved
