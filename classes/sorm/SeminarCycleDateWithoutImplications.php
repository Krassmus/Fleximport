<?php

class SeminarCycleDateWithoutImplications extends SeminarCycleDate
{
    public function store()
    {
        return SimpleORMap::store();
    }
}
