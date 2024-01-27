<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    const EMPLOYEE_COUNT = 10;
    const CUSTOMER_COUNT = 10;
    const COMPANY_COUNT = 3;
    const COMPANY_OWNER_COUNT = 3;
    const PRODUCT_COUNT = 10;
    const CATEGORY_COUNT = 3;

    public function load(ObjectManager $manager): void
    {

    }
}
