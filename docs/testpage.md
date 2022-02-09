# Test page to try out special features from Docsify

!> This is a test error/warning message

Look at this beautiful image

![Images](http://community.myddleware.com/wp-content/uploads/2016/09/myddleware_logo-300x215.jpg)

And here is a test json piece of code

```json
    {
        "name": "Roman",
        "age": 25,
        "hobbies": {
            "videogames": "BOII",
            "books": "Dune- Franck Herbert",
            "films": "Whiplash",
            "sports": "Boxe"
        }
    }
```

This is a test PHP code sample

```php
    class TestClass extends TestClassInterface {
        
        private $property;
        private $property2;
        public __construct()
        {
            $this->property = $property;
            $this->property2 = $property2;
        }

        public function awesomeFunction(int $age, string $name): void
        {
            return echo "My name is $name and I am $age years old";
        }    
    }
```

We can also use SQL syntax

```sql
    SELECT * FROM users WHERE id=? 
```
