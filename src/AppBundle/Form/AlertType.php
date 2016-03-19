<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tags = ['all' => 'all'];
        foreach ($options['tags'] as $tag) {
            $tags[$tag] = $tag;
        }

        $builder
            ->add('tag', Type\ChoiceType::class, [
                'label' => 'Tag',
                'choices' => $tags,
            ])
            ->add('email', Type\EmailType::class, ['label' => 'E-mail address'])
            ->add('frequency', Type\ChoiceType::class, [
                'label' => 'Frequency',
                'choices'  => array(
                    'daily' => 'daily',
                    'weekly' => 'weekly',
                ),])
            ->add('save', Type\SubmitType::class, ['label' => 'Subscribe'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'tags' => [],
        ));
    }
}