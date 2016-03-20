<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('min_date', Type\DateType::class, [
                'label' => 'Starts after (yyyy-mm-dd)',
                'html5' => true,
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('max_date', Type\DateType::class, [
                'label' => 'Ends before',
                'html5' => true,
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('location', Type\TextType::class, [
                'label' => 'Near location',
                'required' => false,
            ])
            ->add('radius', Type\IntegerType::class, [
                'label' => 'Within km radius',
                'required' => false,
            ])
            ->add('cfp_status', Type\ChoiceType::class, [
                'label' => 'Call for papers',
                'choices'  => array(
                    'not open yet' => 'upcoming',
                    'open' => 'open',
                    'closed' => 'closed',
                ),
                'required' => false,
            ])
            ->add('s', Type\SubmitType::class, ['label' => 'Search'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
        ));
    }

    public function getBlockPrefix()
    {
        return null;
    }
}