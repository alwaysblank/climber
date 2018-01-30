<?php // phpcs:disable

class Storage
{

    public static $MenuStringExpected = '<nav class="simpleMenu"><ul class="simpleMenu__menu level-0"><li class="simpleMenu__item"><a href="https://california.gov" class="simpleMenu__link">California</a></li><li class="simpleMenu__item"><a href="https://oregon.gov" class="simpleMenu__link">Oregon</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-1"><li class="simpleMenu__item"><a href="https://oregon.gov/portland" class="simpleMenu__link">Portland</a></li><li class="simpleMenu__item"><a href="https://oregon.gov/corvallis" class="simpleMenu__link">Corvallis</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-2"><li class="simpleMenu__item"><a href="https://oregon.gov/corvallis/osu" class="simpleMenu__link">OSU</a></li></ul></li></ul></li><li class="simpleMenu__item"><a href="https://iowa.gov" class="simpleMenu__link">Iowa</a></li></ul></nav>';

    public static $ActivatedMenuStringExpected = '<nav class="simpleMenu"><ul class="simpleMenu__menu level-0"><li class="simpleMenu__item"><a href="https://california.gov" class="simpleMenu__link">California</a></li><li class="simpleMenu__item simpleMenu__item--ancestor"><a href="https://oregon.gov" class="simpleMenu__link">Oregon</a><ul class="simpleMenu__menu simpleMenu__menu--submenu simpleMenu__menu--active level-1"><li class="simpleMenu__item"><a href="https://oregon.gov/portland" class="simpleMenu__link">Portland</a></li><li class="simpleMenu__item simpleMenu__item--parent"><a href="https://oregon.gov/corvallis" class="simpleMenu__link">Corvallis</a><ul class="simpleMenu__menu simpleMenu__menu--submenu simpleMenu__menu--active level-2"><li class="simpleMenu__item simpleMenu__item--current"><a href="https://oregon.gov/corvallis/osu" class="simpleMenu__link">OSU</a></li></ul></li></ul></li><li class="simpleMenu__item"><a href="https://iowa.gov" class="simpleMenu__link">Iowa</a></li></ul></nav>';
}
// phpcs:enable
