<?php

declare(strict_types=1);

return <<<'EOD'

    /**
     * Proxy function to list() to prevent BC break since 7.4.0
     */
    public function tasksList(array $params = [])
    {
        return $this->list($params);
    }
EOD;
