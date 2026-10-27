using System.Windows;
using PubQuizApp.ViewModels;

namespace PubQuizApp.Views
{
    public partial class PresentationWindow : Window
    {
        public PresentationWindow(QuizSessionViewModel viewModel)
        {
            InitializeComponent();
            DataContext = viewModel;
        }
    }
}
