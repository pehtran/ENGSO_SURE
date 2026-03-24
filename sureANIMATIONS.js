import { animate } from 'https://esm.sh/animejs';

animate('.square', { x: '17rem' });
animate('#css-selector-id', { rotate: '1turn' });
animate('.row:nth-child(3) .square', { scale: [1, .5, 1] });

console.log('ANIME.JS is working!');