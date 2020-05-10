<?php

namespace TinyMediaCenter\API\Model;

/**
 * Class AbstractResourceModel
 */
abstract class AbstractResource implements ResourceInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $includes;

    /**
     * AbstractJsonApiModel constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
        $this->includes = [];
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param array $includes
     */
    public function setIncludes(array $includes)
    {
        $this->includes = $includes;
    }

    /**
     * @return ResourceInterface[]
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        //a minimal resource
        $data = [
            'id' => $this->getId(),
            'type' => $this->getType(),
        ];

        if (!empty($this->getIncludes())) {
            $data['included'] = array_map(function (ResourceInterface $resourceModel) {
                return $resourceModel->toArray();
            }, $this->getIncludes());
        }

        return $data;
    }
}
